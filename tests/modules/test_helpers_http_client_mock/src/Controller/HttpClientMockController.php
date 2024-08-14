<?php

namespace Drupal\test_helpers_http_client_mock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Drupal\test_helpers_http_client_mock\HttpClientFactoryMock;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for the HttpClientMock test helper pages.
 */
class HttpClientMockController extends ControllerBase {

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
   * The HTTP client factory service.
   *
   * @var \Drupal\test_helpers_http_client_mock\HttpClientFactoryMock
   */
  protected HttpClientFactoryMock $httpClientFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->state = $container->get('state');
    $instance->requestStack = $container->get('request_stack');
    $instance->httpClientFactory = $container->get('http_client_factory');
    return $instance;
  }

  /**
   * Sets the settings for the HttpClientFactoryMock settings.
   *
   * Pass the values via GET parameters:
   * - mode: The mode to use: 'store' or 'mock'.
   * - name: The test name
   * - directory: The directory to store the responses.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function setSettings(): JsonResponse {
    $request = $this->requestStack->getCurrentRequest();
    if ($mode = $request->query->get('mode')) {
      $this->state->set(HttpClientFactoryMock::STATE_KEY_REQUEST_MOCK_MODE, $mode);
    }
    if ($name = $request->query->get('name')) {
      $this->state->set(HttpClientFactoryMock::STATE_KEY_TEST_NAME, $name);
    }
    if ($directory = $request->query->get('directory')) {
      $this->state->set(HttpClientFactoryMock::STATE_KEY_RESPONSES_STORAGE_DIRECTORY, $directory);
    }
    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Gets a stored.
   *
   * @param mixed $hash
   *   The hash value.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response.
   */
  public function getStoredResponse($hash): Response {
    $response = $this->httpClientFactory->getStoredResponseByHash($hash);
    return $response;
  }

}
