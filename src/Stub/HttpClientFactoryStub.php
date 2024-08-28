<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Http\ClientFactory;
use Drupal\test_helpers\TestHelpers;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * Helper class to construct a HTTP client for capturing and mocking responses.
 *
 * It allows to grab the responses from the real requests and store them to the
 * files in the 'store' mode.
 *
 * And in the 'mock' mode - read the responses from files without making real
 * HTTP requests.
 *
 * @see `tests/src/Unit/Stub/HttpClientFactoryStubTest.php` for the usage
 * examples.
 */
class HttpClientFactoryStub extends ClientFactory {

  /**
   * The environment variable to control the HTTP client mode - store or mock.
   *
   * @var string
   */
  const EMV_HTTP_CLIENT_MODE = 'TH_HTTP_CLIENT_MODE';
  /**
   * The environment variable value to set the storing to files mode.
   *
   * @var string
   */
  const HTTP_CLIENT_MODE_STORE = 'store';
  /**
   * The environment variable value to set the mocking from files mode.
   *
   * @var string
   */
  const HTTP_CLIENT_MODE_MOCK = 'mock';

  /**
   * The HTTP handler name for the `store` mode.
   *
   * For the `mock` mode no handler is added to the stack, because the default
   * handler is replaced to the MockHandler.
   *
   * @var string
   */
  const HANDLER_NAME_STORE = 'test_helpers_http_client_mock.store_response';

  /**
   * The options keys.
   */
  const OPTION_STORE_HEADERS = 'store_headers';
  const OPTION_STORE_HEADERS_SKIP_KEYS = 'store_headers_skip';
  const OPTION_URI_REGEXP = 'uri_regexp';

  /**
   * Hash storage for the stored and mocked requests.
   *
   * @var array
   */
  protected array $mockedRequestsHashesContainer = [];

  /**
   * HttpClientFactoryStub constructor.
   *
   * @param \GuzzleHttp\HandlerStack|null $stack
   *   The HTTP client stack.
   * @param string|null $requestMockMode
   *   The requests mocking mode: NULL, 'store', 'mock'.
   * @param string|null $responsesStorageDirectory
   *   The directory to store responses.
   * @param string|null $testName
   *   The name of the test.
   * @param string|null $options
   *   An associative array of options.
   *   Supported keys:
   *   - store_headers: (bool) Store headers of the response. Defaults to FALSE.
   *   - skip_headers: (array) A list of headers to not store. Defaults to [].
   *   - uri_regexp: (string) A regular expression for the URI to store.
   *     Defaults to ''.
   */
  public function __construct(
    ?HandlerStack $stack = NULL,
    protected ?string $requestMockMode = NULL,
    protected ?string $responsesStorageDirectory = NULL,
    protected ?string $testName = NULL,
    protected ?array $options = NULL,
  ) {
    $stack = $stack ?? HandlerStack::create();
    $this->options ??= [];
    $this->options += [
      self::OPTION_STORE_HEADERS => FALSE,
      self::OPTION_STORE_HEADERS_SKIP_KEYS => [],
    ];
    $this->setTestName($testName);
    parent::__construct($stack);
  }

  /**
   * {@inheritdoc}
   */
  public function fromOptions(array $config = []) {
    $mode = $this->getRequestMockMode();
    if ($mode == self::HTTP_CLIENT_MODE_STORE) {
      if (!isset($config['handler'])) {
        $config['handler'] = $this->stack;
      }
      $storeResponse = function (callable $handler) {
        return function ($request, array $options) use ($handler) {
          return $handler($request, $options)->then(
            function (ResponseInterface $response) use ($request) {
              if ($this->matchRequest($request)) {
                $this->storeResponse($response, $request);
              }
              return $response;
            }
          );
        };
      };
      $config['handler']->push($storeResponse, self::HANDLER_NAME_STORE);
    }
    elseif ($mode == self::HTTP_CLIENT_MODE_MOCK) {
      $mockResponseHandler = function ($request, $options) {
        if ($this->matchRequest($request)) {
          $response = $this->getStoredResponse($request);
          return new FulfilledPromise($response);
        }
        else {
          $defaultHandler = Utils::chooseHandler();
          return $defaultHandler($request, $options);
        }
      };
      $config['handler'] = new HandlerStack($mockResponseHandler);
    }
    return parent::fromOptions($config);
  }

  /**
   * Gets the stored response of a request from the storage.
   *
   * @param \GuzzleHttp\Psr7\Request $request
   *   A request.
   *
   * @throws \Exception
   *   When there is no stored response found.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The stored response.
   */
  public function getStoredResponse(Request $request): Response {
    $hash = $this->getRequestHash($request);
    $this->storeRequestHash($hash);
    try {
      $response = $this->getStoredResponseByHash($hash);
    }
    catch (\Exception $e) {
      throw new \Exception(
        "No stored response found for the request with the hash $hash in the \"mock\" mode: "
        . $request->getMethod() . ' ' . $request->getUri()
        . " Use the '" . self::EMV_HTTP_CLIENT_MODE . "=store' environment variable to create files with stored responses."
      );
    }
    return $response;
  }

  /**
   * Get the stored response from the storage by the request hash.
   *
   * @param string $hash
   *   A request hash.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The stored response.
   */
  public function getStoredResponseByHash(string $hash): Response {
    $file = $this->getRequestFilename($hash);
    if (!$body = @file_get_contents($file)) {
      throw new \Exception("No stored response found for the hash \"$hash\" in the file " . $file);
    }
    $fileMetadata = $this->getRequestFilename($hash, metadata: TRUE);
    if (!$metadata = json_decode(@file_get_contents($fileMetadata), TRUE)) {
      throw new \Exception("No stored metadata found for the hash \"$hash\" in the file " . $fileMetadata);
    }

    $status = 200;
    $headers = [];
    if (isset($metadata['response'])) {
      $status = $metadata['response']['status'];
      if (
        $this->options[self::OPTION_STORE_HEADERS]
        && isset($metadata['response']['headers'])
      ) {
        $headers = $metadata['response']['headers'];
      }
    }

    $response = new Response(
      status: $status,
      headers: $headers,
      body: $body,
    );
    return $response;
  }

  /**
   * Deletes the stored response files from the storage by the request hash.
   *
   * @param string $hash
   *   A request hash.
   */
  public function deleteStoredResponseByHash(string $hash): void {
    unlink($this->getRequestFilename($hash));
    unlink($this->getRequestFilename($hash, metadata: TRUE));
  }

  /**
   * Gets the current test name, or generates it if not set.
   *
   * @return string
   *   The current test name.
   */
  public function getTestName(): string {
    return $this->testName;
  }

  /**
   * Sets the test name, autodetect the name if not provided.
   *
   * @param string|null $name
   *   The test name. If NULL - tries to autodetect it.
   */
  public function setTestName(?string $name = NULL): void {
    if ($name !== NULL) {
      $this->testName = $name;
    }
    elseif ($this->isPhpunitTest()) {
      // Autodetect the test name from parent callers.
      $backtrace = debug_backtrace();
      foreach ($backtrace as $item) {
        if (
          in_array($item['class'] ?? [], [
            HttpClientFactoryStub::class,
            TestHelpers::class,
          ])) {
          continue;
        }
        $this->testName = $item['class'] . '::' . $item['function'];
        break;
      }
    }
    else {
      // @todo Make auto detection of test name in functional tests.
      $this->testName = 'undefined';
    }
  }

  /**
   * Returns the current HTTP Requests mocking mode: none, store, mock.
   *
   * If the mode is not set explicitly, it is controllable by the
   * `TH_HTTP_CLIENT_MODE` environment variable:
   * - `TH_HTTP_CLIENT_MODE=store` enables real requests and store them.
   * - `TH_HTTP_CLIENT_MODE=mock` mock all requests from the storage.
   *
   * @return string|null
   *   The current mode:
   *   - NULL - works as default Drupal http_client
   *   - store - stores all response to the storage.
   *   - mock - mocks all requests from the storage.
   */
  public function getRequestMockMode(): ?string {
    if ($this->requestMockMode === NULL) {
      switch (getenv(self::EMV_HTTP_CLIENT_MODE)) {
        case self::HTTP_CLIENT_MODE_STORE:
          return self::HTTP_CLIENT_MODE_STORE;

        case self::HTTP_CLIENT_MODE_MOCK:
          return self::HTTP_CLIENT_MODE_MOCK;

        default:
          return NULL;
      }
    }
    else {
      return $this->requestMockMode;
    }
  }

  /**
   * Sets the HTTP Requests mocking mode.
   *
   * @param mixed $mode
   *   A mode: store, mock, or NULL to use the Drupal default mode.
   */
  public function setRequestMockMode(string $mode): void {
    $this->requestMockMode = $mode;
  }

  /**
   * Stores the response for a request to the storage.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   The response to store.
   * @param \GuzzleHttp\Psr7\Request $request
   *   The request, is used to generate the hash.
   * @param ?string $hash
   *   The custom hash value to use when storing.
   *   Useful when you need to store a modified response.
   */
  public function storeResponse(Response $response, Request $request = NULL, string $hash = NULL) {
    $hash ??= $this->getRequestHash($request);
    $filename = $this->getRequestFilename($hash);
    $body = $response->getBody();
    $body->rewind();
    file_put_contents($filename, $body->getContents());
    $body->rewind();
    $testName = $this->getTestName();

    $metadataFilename = $this->getRequestFilename($hash, metadata: TRUE);
    $metadata = [
      'tests' => [],
      'response' => [
        'status' => $response->getStatusCode(),
      ],
    ];
    if ($this->options[self::OPTION_STORE_HEADERS]) {
      $metadata['response']['headers'] = $response->getHeaders();
      if ($this->options[self::OPTION_STORE_HEADERS_SKIP_KEYS]) {
        foreach ($this->options[self::OPTION_STORE_HEADERS_SKIP_KEYS] as $header) {
          unset($metadata['response']['headers'][$header]);
        }
      }
    }
    if ($request) {
      $metadata['request'] = $this->getRequestMetadata($request);
    }
    if (file_exists($metadataFilename)) {
      $metadataStoredContent = file_get_contents($metadataFilename);
      $metadataStored = json_decode($metadataStoredContent, TRUE) ?? [];
      $metadata['tests'] = $metadataStored['tests'];
    }

    $metadata['tests'][] = $testName;
    ksort($metadata['tests']);
    $metadata['tests'] = array_unique($metadata['tests']);

    $metadataContent = json_encode($metadata, JSON_PRETTY_PRINT);
    if ($metadataStoredContent ?? '' !== $metadataContent) {
      file_put_contents($metadataFilename, $metadataContent);
    }
    $this->storeRequestHash($hash);
  }

  /**
   * Gets the stored response filename from a Request.
   *
   * @param \GuzzleHttp\Psr7\Request $request
   *   A request.
   * @param bool $metadata
   *   A flag to return the metadata filename.
   *
   * @return string
   *   A full path to the stored response file.
   */
  public function getRequestFilenameFromRequest(Request $request, bool $metadata = FALSE): string {
    return $this->getRequestFilename($this->getRequestHash($request), $metadata);
  }

  /**
   * Gets the request filename by the hash.
   *
   * @param string $hash
   *   A hash.
   * @param bool $metadata
   *   A flag to return the metadata file instead.
   *
   * @return string
   *   A full path to the file.
   */
  public function getRequestFilename(string $hash, bool $metadata = FALSE): string {
    $directory = $this->getResponsesStorageDirectory();
    if ($metadata) {
      $hash = $hash . '_metadata';
    }
    $filename = "$directory/$hash.json";
    return $filename;
  }

  /**
   * Generates a hash for a request.
   *
   * @param \GuzzleHttp\Psr7\Request $request
   *   A request.
   *
   * @return string[]
   *   An associative array with the request metadata:
   *   - method: The request method.
   *   - uri: The request URI.
   *   - body: The request body, if not empty.
   */
  protected function getRequestMetadata($request): array {
    $metadata = [
      'method' => $request->getMethod(),
      'uri' => $request->getUri()->__toString(),
    ];
    $body = $request->getBody();
    if ($body->getSize() > 0) {
      $body->rewind();
      $metadata['body'] = $body->getContents();
    }
    return $metadata;
  }

  /**
   * Generates a hash for a request.
   *
   * @param \GuzzleHttp\Psr7\Request $request
   *   A request.
   *
   * @return string
   *   The generated hash.
   */
  public function getRequestHash($request): string {
    return md5(json_encode($this->getRequestMetadata($request)));
  }

  /**
   * Checks if the current test using PHPUnit.
   *
   * @return bool
   *   True if PHPUnit, false otherwise.
   */
  protected function isPhpunitTest(): bool {
    return defined('PHPUNIT_COMPOSER_INSTALL');
  }

  /**
   * Gets the URI regular expression.
   *
   * @return string|null
   *   The URI regular expression.
   */
  public function getUriRegexp(): ?string {
    return $this->options[self::OPTION_URI_REGEXP];
  }

  /**
   * Sets the URI regular expression.
   *
   * @param string|null $regexp
   *   The URI regular expression.
   */
  public function setUriRegexp(?string $regexp): void {
    $this->options[self::OPTION_URI_REGEXP] = $regexp;
  }

  /**
   * Matches a request against the URI regular expression.
   *
   * @param \GuzzleHttp\Psr7\Request $request
   *   The request to match.
   *
   * @return bool
   *   TRUE if the request matches the URI regular expression, FALSE otherwise.
   */
  protected function matchRequest(Request $request): bool {
    if ($this->options[self::OPTION_URI_REGEXP] ?? NULL) {
      return preg_match($this->options[self::OPTION_URI_REGEXP], $request->getUri()->__toString());
    }
    return TRUE;
  }

  /**
   * Returns the current responses storage directory.
   */
  public function getResponsesStorageDirectory(): string {
    if ($this->responsesStorageDirectory === NULL) {
      throw new \Exception('To use the `store` and `mock` modes, you need to set the `responsesStorageDirectory` property.');
    }
    if (!is_dir($this->responsesStorageDirectory)) {
      mkdir($this->responsesStorageDirectory, recursive: TRUE);
    }
    return $this->responsesStorageDirectory;
  }

  /**
   * Sets the current responses storage directory.
   */
  public function setResponsesStorageDirectory(string $directory): void {
    $this->responsesStorageDirectory = $directory;
  }

  /**
   * Adds the request hash to the container.
   *
   * @param string $hash
   *   A hash value.
   */
  protected function storeRequestHash(string $hash): void {
    $this->mockedRequestsHashesContainer[] = $hash;
  }

  /**
   * Returns the list of hashes of all mocked responses.
   *
   * @return array
   *   A list of hashes of all mocked responses.
   */
  public function getMockedRequestsHashesContainer(): array {
    return $this->mockedRequestsHashesContainer;
  }

}
