<?php

declare(strict_types=1);

namespace Drupal\test_helpers_http_client_mock;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
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
   * The key to store the requests mocking mode in the configuration.
   *
   * @var string
   */

  const SETTINGS_CONFIG_KEY = 'test_helpers_http_client_mock.settings';
  /**
   * The key to store the requests mocking mode in the configuration.
   *
   * @var string
   */
  const SETTING_KEY_REQUEST_MOCK_MODE = 'request_mock_mode';

  /**
   * The key to store the responses storage directory in the configuration.
   *
   * @var string
   */
  const SETTING_KEY_RESPONSES_STORAGE_DIRECTORY = 'responses_storage_directory';

  /**
   * The key to store the log stored responses file in the configuration.
   *
   * @var string
   */
  const SETTING_KEY_LOG_STORED_RESPONSES_USAGE_FILE = 'log_stored_responses_usage_file';

  /**
   * The key to store the test name in the configuration.
   *
   * @var string
   */
  const SETTING_KEY_TEST_NAME = 'test_name';

  /**
   * The key to store the URI regular expression in the configuration.
   *
   * @var string
   */
  const SETTING_KEY_URI_REGEXP = 'uri_regexp';

  /**
   * The key to store the list of requests hashes in the State.
   *
   * @var string
   */
  const STATE_KEY_LAST_REQUESTS_HASHES = 'test_helpers_http_client_mock.last_requests_hashes';

  /**
   * The custom HTTP header name to pass the stored requests hashes.
   *
   * @var string
   */
  const HTTP_HEADER_NAME = 'X-Test-Helpers-Mocked-Requests-Hashes';

  /**
   * The custom meta tag name to pass the stored requests hashes.
   *
   * @var string
   */
  const META_TAG_NAME = 'TestHelpersHttpClientMockRequestsHashes';

  /**
   * The custom meta tag key to use in the Drupal render array.
   *
   * @var string
   */
  const META_TAG_KEY = 'test_helpers_http_client_mock_requests_hashes';

  /**
   * HttpClientFactoryMock constructor.
   *
   * @param \GuzzleHttp\HandlerStack $stack
   *   The GuzzleHttp handler stack.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
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
    protected ConfigFactoryInterface $configFactory,
    protected ?string $requestMockMode = NULL,
    protected ?string $responsesStorageDirectory = NULL,
    protected ?string $testName = NULL,
    protected ?string $uriRegexp = NULL,
  ) {
    $this->requestMockMode ??= $this->stubGetConfig(self::SETTING_KEY_REQUEST_MOCK_MODE);
    $this->responsesStorageDirectory ??= $this->stubGetConfig(self::SETTING_KEY_RESPONSES_STORAGE_DIRECTORY);
    $this->testName ??= $this->stubGetConfig(self::SETTING_KEY_TEST_NAME);
    $this->uriRegexp ??= $this->stubGetConfig(self::SETTING_KEY_URI_REGEXP);

    $options = [
      HttpClientFactoryStub::OPTION_URI_REGEXP => $this->uriRegexp,
      HttpClientFactoryStub::OPTION_LOG_STORED_RESPONSES_USAGE_FILE => $this->stubGetConfig(self::SETTING_KEY_LOG_STORED_RESPONSES_USAGE_FILE),
    ];

    parent::__construct(
      $stack,
      $this->requestMockMode,
      $this->responsesStorageDirectory,
      $this->testName,
      $options,
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
    if ($hashes = $this->stubGetMockedRequestsHashesContainer()) {
      $response = $event->getResponse();
      $response->headers->set(self::HTTP_HEADER_NAME, json_encode($hashes));
    }
  }

  /**
   * Stores the request has to the state, to retrieve a list of last requests.
   *
   * @param string $hash
   *   A hash value.
   */
  protected function stubStoreRequestHash(string $hash): void {
    parent::stubStoreRequestHash($hash);
    $lastHashes = $this->state->get(self::STATE_KEY_LAST_REQUESTS_HASHES, []);
    array_unshift($lastHashes, $hash);
    $lastHashes = array_slice($lastHashes, 0, 32);
    $this->state->set(self::STATE_KEY_LAST_REQUESTS_HASHES, $lastHashes);
  }

  /**
   * Retrieves a configuration value by key.
   *
   * @param string $key
   *   The configuration key.
   *
   * @return mixed
   *   The configuration value.
   */
  public function stubGetConfig(string $key) {
    return $this->configFactory->get(self::SETTINGS_CONFIG_KEY)->get($key);
  }

  /**
   * Retrieves a configuration object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  public function stubGetConfiguration(): ImmutableConfig {
    return $this->configFactory->get(self::SETTINGS_CONFIG_KEY);
  }

  /**
   * Sets a configuration value by key.
   *
   * @param string $key
   *   The configuration key.
   * @param mixed $value
   *   The configuration value.
   */
  public function stubSetConfig(string $key, mixed $value): void {
    $this->configFactory->getEditable(self::SETTINGS_CONFIG_KEY)
      ->set($key, $value)
      ->save();
  }

}
