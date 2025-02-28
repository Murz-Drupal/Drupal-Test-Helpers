<?php

declare(strict_types=1);

namespace Drupal\test_helpers_http_client_mock\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\test_helpers_http_client_mock\HttpClientFactoryMock;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Controller for the HttpClientMock test helper pages.
 */
class HttpClientMockController extends ControllerBase {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The HTTP client factory service.
   *
   * @var \Drupal\test_helpers_http_client_mock\HttpClientFactoryMock
   */
  protected HttpClientFactoryMock $httpClientFactory;

  /**
   * The Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected LockBackendInterface $lock;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->stateService = $container->get('state');
    $instance->requestStack = $container->get('request_stack');
    $instance->httpClientFactory = $container->get('http_client_factory');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->lock = $container->get('lock');
    return $instance;
  }

  /**
   * Gets the settings for the HttpClientFactoryMock settings.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function stubGetSettings(): JsonResponse {
    return new JsonResponse($this->httpClientFactory->stubGetConfiguration()->getRawData());
  }

  /**
   * Sets the settings for the HttpClientFactoryMock settings.
   *
   * Pass the values via GET parameters:
   * - mode: The mode to use: 'store' or 'mock'.
   * - name: The test name.
   * - module: The module name, used to get the module path for the directory/
   * - directory: The directory to store the responses. Absolute path, or
   *   relative to the module path, if the module parameter is set.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function stubSetSettings(): JsonResponse {
    $request = $this->requestStack->getCurrentRequest();
    if ($mode = $request->query->get('mode')) {
      $this->httpClientFactory->stubSetConfig(HttpClientFactoryMock::SETTING_KEY_REQUEST_MOCK_MODE, $mode);
    }
    if ($name = $request->query->get('name')) {
      $this->httpClientFactory->stubSetConfig(HttpClientFactoryMock::SETTING_KEY_TEST_NAME, $name);
    }
    if ($directory = $request->query->get('directory')) {
      if (
        !str_starts_with($directory, '/')
        && $module = $request->query->get('module')
      ) {
        $modulePath = $this->moduleHandler->getModule($module)->getPath();
        $directory = $modulePath . DIRECTORY_SEPARATOR . $directory;
      }
      $this->httpClientFactory->stubSetConfig(HttpClientFactoryMock::SETTING_KEY_RESPONSES_STORAGE_DIRECTORY, $directory);
    }
    if ($uriRegexp = $request->query->get('uri_regexp')) {
      $this->httpClientFactory->stubSetConfig(HttpClientFactoryMock::SETTING_KEY_URI_REGEXP, $uriRegexp);
    }
    return new JsonResponse([
      'status' => 'success',
      'settings' => $this->httpClientFactory->stubGetConfiguration()->getRawData(),
    ]);
  }

  /**
   * Gets a stored response.
   *
   * @param mixed $hash
   *   The hash value.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function stubGetStoredResponse($hash): SymfonyResponse {
    if ($this->requestStack->getCurrentRequest()->query->get('metadata') == TRUE) {
      $body = $this->httpClientFactory->stubGetStoredResponseMetadataByHash($hash);
      $symfonyResponse = new SymfonyResponse(json_encode($body));
      $symfonyResponse->headers->set('Content-Type', 'application/json');
    }
    else {
      $response = $this->httpClientFactory->stubGetStoredResponseByHash($hash);
      $body = $response->getBody()->getContents();
      $symfonyResponse = new SymfonyResponse($body, $response->getStatusCode(), $response->getHeaders());
      $symfonyResponse->headers->set('Content-Type', 'text/plain');
    }

    // Add headers to make the HTTP response non-cacheable by browsers and
    // proxies.
    $symfonyResponse->headers->set('Cache-Control', 'no-store');

    // Ensure the response is not cacheable by Drupal's internal cache systems.
    if ($symfonyResponse instanceof CacheableResponseInterface) {
      $symfonyResponse->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
    }
    return $symfonyResponse;
  }

  /**
   * Delete the stored response files.
   *
   * @param mixed $hash
   *   The hash value.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  public function stubDeleteStoredResponse($hash): JsonResponse {
    $this->httpClientFactory->stubDeleteStoredResponseByHash($hash);
    return new JsonResponse(['status' => 'success']);
  }

  /**
   * Sets the stored response data.
   *
   * @param mixed $hash
   *   The hash value.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  public function stubSetStoredResponse($hash): JsonResponse {
    $request = $this->requestStack->getCurrentRequest();
    $data = $request->getContent();
    $status = $request->query->get('status', 200);
    $headers = $request->query->has('headers')
      ? json_decode($request->query->get('headers'))
      : [];
    $response = new Response($status, $headers, $data);
    $this->httpClientFactory->stubStoreResponse($response, NULL, $hash);
    return new JsonResponse(['status' => 'success']);
  }

  /**
   * Gets the last requests hashes.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  public function stubGetLastRequestsHashes(): JsonResponse {
    $this->lock->wait(HttpClientFactoryMock::LOCK_KEY_LAST_REQUESTS_HASHES_UPDATE);
    $data = $this->stateService->get(HttpClientFactoryMock::STATE_KEY_LAST_REQUESTS_HASHES, []);
    return new JsonResponse($data);
  }

  /**
   * Gets the last response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function stubGetLastResponse($delta = 0): SymfonyResponse {
    $this->lock->wait(HttpClientFactoryMock::LOCK_KEY_LAST_REQUESTS_HASHES_UPDATE);
    $lastHashes = $this->stateService->get(HttpClientFactoryMock::STATE_KEY_LAST_REQUESTS_HASHES, []);
    $hash = $lastHashes[$delta] ?? NULL;
    return $this->stubGetStoredResponse($hash);
  }

}
