<?php

declare(strict_types=1);

namespace Drupal\test_helpers_functional\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\test_helpers_functional\EventSubscriber\TestHelpersFunctionalEventSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the TestHelpersFunctional HTTP API pages.
 */
class TestHelpersFunctionalController extends ControllerBase {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected ModuleInstallerInterface $moduleInstaller;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->container = $container;
    $instance->state = $container->get('state');
    $instance->requestStack = $container->get('request_stack');
    $instance->moduleInstaller = $container->get('module_installer');
    $instance->cacheTagsInvalidator = $container->get('cache_tags.invalidator');
    return $instance;
  }

  /**
   * Sets the env variables from GET parameters.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The operation status.
   */
  public function setEnvs(): JsonResponse {
    $currentRequest = $this->requestStack->getCurrentRequest();
    $envVariables = $currentRequest->query->all();
    if (empty($envVariables)) {
      return new JsonResponse(
        [
          'status' => 'error',
          'message' => 'No environment variables provided',
          'details' => 'Please provide environment variables as key-value GET parameters.',
        ],
      );
    }
    $currentVariables = $this->state->get(TestHelpersFunctionalEventSubscriber::STATE_KEY_ENV_VARIABLES, []);
    $storeVariables = array_merge($currentVariables, $envVariables);
    $this->state->set(TestHelpersFunctionalEventSubscriber::STATE_KEY_ENV_VARIABLES, $storeVariables);
    return new JsonResponse(
      ['status' => 'success'],
    );
  }

  /**
   * Shows an env variable value.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The operation status.
   */
  public function getEnv(string $name): Response {
    $value = getenv($name);
    return new Response($value);
  }

  /**
   * Install and uninstall modules.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The operation status.
   */
  public function moduleInstaller(): Response {
    $query = $this->requestStack->getCurrentRequest()->query;
    $enableDependencies = ($query->get('enable_dependencies') == TRUE);
    $response = [];
    $finalStatus = TRUE;
    if ($installString = $query->get('install')) {
      $installList = explode(',', $installString);
      $installStatus = $this->moduleInstaller->install($installList, $enableDependencies);
      $response['data']['install'] = [
        'result' => $installStatus,
        'modules' => $installList,
      ];
      if (!$installStatus) {
        $finalStatus = FALSE;
      }
    }
    $uninstallList = explode(',', $query->get('uninstall') ?? '');
    if ($uninstallString = $query->get('uninstall')) {
      $uninstallList = explode(',', $uninstallString);
      $uninstallStatus = $this->moduleInstaller->uninstall($uninstallList, $enableDependencies);
      $response['data']['uninstall'] = [
        'result' => $uninstallStatus,
        'modules' => $uninstallList,
      ];
      if (!$uninstallStatus) {
        $finalStatus = FALSE;
      }
    }
    if (!$installString && !$uninstallString) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'No modules to install or uninstall',
        'details' => "Please provide a list of modules to install or uninstall, using GET parameters 'install' and 'uninstall', separate module names with commas. Use 'enable_dependencies' to enable dependencies.",
      ]);
    }
    $response['status'] = $finalStatus ? 'success' : 'error';
    return new JsonResponse($response);
  }

  /**
   * Logs in as a user by name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The operation status.
   */
  public function login(string $name) {
    /**
     * @var \Drupal\user\UserInterface $user
     */
    $user = current($this->entityTypeManager()->getStorage('user')
      ->loadByProperties(['name' => $name]));
    if (!$user) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'User not found',
        'details' => "No user found with the name '$name'.",
      ]);
    }
    user_login_finalize($user);
    return new JsonResponse(
      [
        'status' => 'success',
        'data' => $user->toArray(),
      ],
    );
  }

  /**
   * Logs out from a user session.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The operation status.
   */
  public function logout() {
    user_logout();
    return new JsonResponse(
      ['status' => 'success'],
    );
  }

  /**
   * Creates a new user.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The operation status.
   */
  public function createUser(): Response {
    // @todo Add creating permissions.
    $userData = $this->requestStack->getCurrentRequest()->query->all();
    if (empty($userData['name'])) {
      $userData['name'] = 'user_' . uniqid();
    }

    if ($userData['__login'] ?? NULL) {
      $logIn = TRUE;
      unset($userData['__login']);
    }
    else {
      $logIn = FALSE;
    }

    // Setting the default status "1" if not set, to make the user
    // active by default.
    $userData['status'] ??= 1;

    /**
     * @var \Drupal\user\UserInterface $user
     */
    $user = $this->entityTypeManager()->getStorage('user')->create($userData);
    if (!$user || $user->name->value !== $userData['name']) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'User not created',
        'details' => 'User creation failed.',
      ]);
    }

    if ($userData['password'] ?? NULL) {
      $user->setPassword($userData['password']);
    }

    // Creating a new role with permissions, if permissions are provided.
    if (!empty($userData['permissions'])) {
      if (is_string($userData['permissions'])) {
        $userData['permissions'] = explode(',', $userData['permissions']);
      }
      $roleName = 'role_' . uniqid();
      /**
       * @var \Drupal\user\RoleInterface $role
       */
      $role = $this->entityTypeManager()->getStorage('user_role')
        ->create(['id' => $roleName, 'label' => "Role $roleName"]);
      foreach ($userData['permissions'] as $permission) {
        $role->grantPermission($permission);
      }
      $role->save();
      $user->addRole($role->id());
      unset($userData['permissions']);
    }

    $user->save();

    if ($logIn) {
      $this->login($userData['name']);
    }

    return new JsonResponse([
      'status' => 'success',
      'data' => $user->toArray(),
    ]);
  }

  /**
   * Creates a new user.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The operation status.
   */
  public function cacheClear(): Response {
    $params = $this->requestStack->getCurrentRequest()->query->all();
    if (!empty($params['bins'])) {
      $bins = explode(',', $params['bins']);
      foreach ($bins as $bin) {
        if (!$binService = Cache::getBins()[$bin]) {
          throw new \Exception("Cache bin '$bin' not found.");
        }
        $binService->deleteAll();
      }
    }
    if (!empty($params['tags'])) {
      $tags = explode(',', $params['tags']);
      $this->cacheTagsInvalidator->invalidateTags($tags);
    }
    if (empty($params)) {
      drupal_flush_all_caches();
    }
    return new JsonResponse([
      'status' => 'success',
    ]);
  }

}
