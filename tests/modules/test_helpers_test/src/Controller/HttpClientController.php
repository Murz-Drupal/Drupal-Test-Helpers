<?php

namespace Drupal\test_helpers_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\State\StateInterface;
use Drupal\test_helpers_http_client_mock\HttpClientFactoryMock;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for the HttpClientMock test helper pages.
 */
class HttpClientController extends ControllerBase {

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
   * @var \Drupal\Core\Http\ClientFactory|\Drupal\test_helpers_http_client_mock\HttpClientFactoryMock
   */
  protected ClientFactory $httpClientFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->requestStack = $container->get('request_stack');
    $instance->httpClientFactory = $container->get('http_client_factory');
    return $instance;
  }

  /**
   * Makes an outgoing HTTP call and renders the response.
   *
   * @return array
   *   The render array.
   */
  public function httpCallRender(): array {
    $currentRequest = $this->requestStack->getCurrentRequest();
    if (!$path = $currentRequest->query->get('path')) {
      throw new \InvalidArgumentException('The "path" query parameter is required.');
    }
    $fullPath = base_path() . ltrim($path, '/');
    $baseUri = $currentRequest->getSchemeAndHttpHost();
    $url = $baseUri . $fullPath;
    $request = $this->httpClientFactory
      ->fromOptions(['base_uri' => $baseUri])
      ->request('GET', $fullPath);
    $responseBody = $request
      ->getBody();
    $responseBody->rewind();
    $response = $responseBody->getContents();
    $output['response'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#prefix' => 'Response body:',
      '#value' => $response,
      '#attributes' => [
        'id' => ['http-call-render-response'],
      ],
      '#cache' => [
        '#max-age' => 0,
      ],
    ];
    $metadata = 'Test name: ' . $this->state()->get(HttpClientFactoryMock::STATE_KEY_TEST_NAME)
      . PHP_EOL . 'Mode: ' . $this->state()->get(HttpClientFactoryMock::STATE_KEY_REQUEST_MOCK_MODE)
      . PHP_EOL . 'Directory: ' . $this->state()->get(HttpClientFactoryMock::STATE_KEY_RESPONSES_STORAGE_DIRECTORY)
      . PHP_EOL . 'Request URI: ' . $url;

    $output['metadata'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#prefix' => 'Test Helpers settings:',
      '#value' => $metadata,
      '#attributes' => [
        'id' => ['http-call-render-metadata'],
      ],
      '#cache' => [
        '#max-age' => 0,
      ],
    ];

    $output['hash'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#prefix' => 'Request hash:',
      '#value' => implode(', ', $this->httpClientFactory->getMockedRequestsHashesContainer()),
      '#attributes' => [
        'id' => ['http-call-render-request-hash'],
      ],
      '#cache' => [
        '#max-age' => 0,
      ],
    ];

    return $output;
  }

  /**
   * Generates a response with JSON content - test 1 asset.
   *
   * @return array
   *   The render array.
   */
  public function jsonResponse1(): JsonResponse {
    return new JsonResponse(['title' => 'foo1']);
  }

  /**
   * Generates a response with JSON content - test 2 asset.
   *
   * @return array
   *   The render array.
   */
  public function jsonResponse2(): JsonResponse {
    return new JsonResponse(
      ['title' => 'foo2', 'bar' => 'baz'],
      status: 201,
    );
  }

}
