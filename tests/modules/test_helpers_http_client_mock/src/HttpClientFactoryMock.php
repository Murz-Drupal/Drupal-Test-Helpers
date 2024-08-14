<?php

namespace Drupal\test_helpers_http_client_mock;

use Drupal\Core\State\StateInterface;
use Drupal\test_helpers\Stub\HttpClientFactoryStub;
use GuzzleHttp\HandlerStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Extension of the HttpClientFactoryStub service for functional tests.
 *
 * Overrides the constructor to set the test configuration from the State.
 *
 * Also, adds a custom HTTP header to the response with used requests hashes.
 */
class HttpClientFactoryMock extends HttpClientFactoryStub implements EventSubscriberInterface {

  /**
   * The key to store the requests mocking mode in the State.
   *
   * @var string
   */
  const STATE_KEY_REQUEST_MOCK_MODE = 'test_helpers_http_client_mock.request_mock_mode';

  /**
   * The key to store the responses storage directory in the State.
   *
   * @var string
   */
  const STATE_KEY_RESPONSES_STORAGE_DIRECTORY = 'test_helpers_http_client_mock.responses_storage_directory';

  /**
   * The key to store the test name in the State.
   *
   * @var string
   */
  const STATE_KEY_TEST_NAME = 'test_helpers_http_client_mock.test_name';

  /**
   * The custom HTTP header name to pass the stored requests hashes.
   *
   * @var string
   */
  const HTTP_HEADER_NAME = 'X-Test-Helpers-Mocked-Responses-Hashes';

  /**
   * HttpClientFactoryMock constructor.
   *
   * @param \GuzzleHttp\HandlerStack $stack
   *   The GuzzleHttp handler stack.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state service.
   * @param string|null $requestMockMode
   *   The requests mocking mode: NULL, 'store', 'mock'.
   * @param string|null $responsesStorageDirectory
   *   The directory to store responses.
   * @param string|null $testName
   *   The name of the test.
   * @param string|null $uriRegexp
   *   A regular expression to match URIs and process only matched ones.
   */
  public function __construct(
    HandlerStack $stack,
    protected StateInterface $state,
    protected ?string $requestMockMode = NULL,
    protected ?string $responsesStorageDirectory = NULL,
    protected ?string $testName = NULL,
    protected ?string $uriRegexp = NULL,
  ) {
    $requestMockMode ??= $state->get(self::STATE_KEY_REQUEST_MOCK_MODE);
    $responsesStorageDirectory ??= $state->get(self::STATE_KEY_RESPONSES_STORAGE_DIRECTORY);
    $testName ??= $state->get(self::STATE_KEY_TEST_NAME);

    $stack = $stack ?? HandlerStack::create();
    parent::__construct(
      $stack,
      $requestMockMode,
      $responsesStorageDirectory,
      $testName,
      $uriRegexp,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Respond to the kernel.response event and call onRespond().
    $events[KernelEvents::RESPONSE][] = 'onRespond';
    return $events;
  }

  /**
   * Adds a custom header to the response with requests hashes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   A response event.
   */
  public function onRespond(ResponseEvent $event) {
    if ($hashes = $this->getMockedRequestsHashesContainer()) {
      $response = $event->getResponse();
      $response->headers->set(self::HTTP_HEADER_NAME, json_encode($hashes));
    }
  }

}
