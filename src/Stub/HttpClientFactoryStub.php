<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Http\ClientFactory;
use Drupal\test_helpers\TestHelpers;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
   * The mode to store all outgoing requests responses to files.
   *
   * @var string
   */
  const HTTP_CLIENT_MODE_STORE = 'store';
  /**
   * The mode to mock all outgoing requests responses from files.
   *
   * @var string
   */
  const HTTP_CLIENT_MODE_MOCK = 'mock';
  /**
   * The mode to store requests responses only if the stored one is missing.
   *
   * @var string
   */
  const HTTP_CLIENT_MODE_APPEND = 'append';

  /**
   * The HTTP handler names to find them in the stack.
   *
   * @var string
   */
  const HANDLER_NAME_CUSTOM = 'test_helpers_http_client_mock.handler_custom';
  const HANDLER_NAME_CUSTOM_RESPONSES_STACK = 'test_helpers_http_client_mock.handler_custom_responses_stack';
  const HANDLER_NAME_BEFORE_REAL_CALL = 'test_helpers_http_client_mock.handler_before_real_call';
  const HANDLER_NAME_AFTER_REAL_CALL = 'test_helpers_http_client_mock.handler_after_real_call';

  /**
   * The options keys.
   */
  const OPTION_STORE_HEADERS = 'store_headers';
  const OPTION_STORE_HEADERS_SKIP_KEYS = 'store_headers_skip';
  const OPTION_LOG_STORED_RESPONSES_USAGE_FILE = 'log_stored_responses_usage_file';
  const OPTION_CONTEXT = 'context';
  const OPTION_URI_REGEXP = 'uri_regexp';

  /**
   * A storage for used (stored and mocked) responses by the usage order.
   *
   * @var array
   */
  protected array $handledRequests = [];

  /**
   * A stack with custom responses.
   *
   * @var \GuzzleHttp\Psr7\Response[]
   */
  protected array $stubCustomResponsesStack = [];

  /**
   * The handler stack, attached to the last created HTTP client.
   *
   * Used to dynamically control the custom handler.
   *
   * @var \GuzzleHttp\HandlerStack|null
   */
  protected $stubHandlerStackLast = NULL;
  /**
   * A custom handler for HTTP requests.
   *
   * @var callable|null
   */
  protected $stubHandlerCustom = NULL;

  /**
   * The context for the stub that is used to generate the stored assets hash.
   *
   * @var string|null
   */
  protected ?string $stubContext = NULL;

  /**
   * HttpClientFactoryStub constructor.
   *
   * @param \GuzzleHttp\HandlerStack|null $stack
   *   The HTTP client stack.
   * @param string|null $requestMockMode
   *   The requests mocking mode: NULL, 'store', 'mock', 'append'.
   *   - append: makes a new request only if the stored response is missing.
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
      self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE => NULL,
    ];
    if (array_key_exists(self::OPTION_CONTEXT, $this->options)) {
      $this->stubContext = $this->options[self::OPTION_CONTEXT];
    }
    $this->stubSetTestName($testName);
    parent::__construct($stack);
  }

  /**
   * {@inheritdoc}
   */
  public function fromOptions(array $config = []) {
    $config = $this->stubAlterConfigOptions($config);
    return parent::fromOptions($config);
  }

  /**
   * Allows altering config options before creating the client.
   *
   * @param array $config
   *   The configuration options for the HTTP client.
   *
   * @return array
   *   The altered configuration options.
   */
  protected function stubAlterConfigOptions(array $config = []): array {
    // Setting the default handler, if the custom one is not set.
    $config['handler'] ??= $this->stack;

    $this->stubHandlerStackLast = $config['handler'];

    $lastMockingResult = NULL;

    // A request handler that executes before all other handlers.
    $handlerBeforeRealCall = function (callable $handler) use (&$lastMockingResult) {
      // @todo Simplify this by not run the custom function if no needs.
      return function ($request, array $options) use ($handler, &$lastMockingResult) {
        $lastMockingResult = NULL;
        if (
          in_array($this->stubGetRequestMockMode(), [
            self::HTTP_CLIENT_MODE_MOCK,
            self::HTTP_CLIENT_MODE_APPEND,
          ])
          && $this->stubMatchRequest($request)
        ) {

          // For the append mode, we should check if the response is already
          // stored and do not produce an exception on missing stored response.
          if ($this->stubGetRequestMockMode() == self::HTTP_CLIENT_MODE_APPEND) {
            if ($this->stubHasStoredResponse($request)) {
              $response = $this->stubGetStoredResponse($request);
              $lastMockingResult = TRUE;
              return new FulfilledPromise($response);
            }
            else {
              $lastMockingResult = FALSE;
            }
          }
          else {
            $response = $this->stubGetStoredResponse($request);
            return new FulfilledPromise($response);
          }
        }
        // If the response returns non-2xx result, the Guzzle will throw an
        // exception, that stops processing next handlers.
        // So we should catch it and store the response here.
        try {
          $handlerResult = $handler($request, $options);
          return $handlerResult;
        }
        catch (BadResponseException $e) {
          if (
            in_array($this->stubGetRequestMockMode(), [
              self::HTTP_CLIENT_MODE_STORE,
              self::HTTP_CLIENT_MODE_APPEND,
            ])
            && $this->stubMatchRequest($request)
          ) {
            $failedResponse = $e->getResponse();
            $this->stubStoreResponse($failedResponse, $request);
          }
          throw $e;
        }
      };
    };

    // A request handler that executes after all other handlers.
    $handlerAfterRealCall = function (callable $handler) use (&$lastMockingResult) {
      // @todo Simplify this by not run the custom function if no needs.
      return function ($request, array $options) use ($handler, &$lastMockingResult) {
        if (
          (
            $this->stubGetRequestMockMode() == self::HTTP_CLIENT_MODE_STORE
            || $lastMockingResult === FALSE
          )
        ) {
          if ($this->stubMatchRequest($request)) {
            // Execute the real request to get the response.
            try {
              $response = $handler($request, $options)->then(
                function ($response) use ($request) {
                  $this->stubStoreResponse($response, $request);
                  return $response;
                }
              );
              return $response;
            }
            catch (BadResponseException $e) {
              // If retrieving the response failed, we should store the
              // response from the exception.
              $response = $e->getResponse();
              $this->stubStoreResponse($response, $request);
            }
          }
        }
        return $handler($request, $options);
      };
    };

    // A request handler that executes before all other handlers.
    $handlerCustomResponsesStack = function (callable $handler) {
      // @todo Simplify this by not run the custom function if no needs.
      return function ($request, array $options) use ($handler) {
        if (!empty($this->stubCustomResponsesStack)) {
          $response = array_shift($this->stubCustomResponsesStack);
          return new FulfilledPromise($response);
        }
        return $handler($request, $options);
      };
    };

    // Add custom handlers to the stack.
    // And clean up already added our handlers, if present.
    $config['handler']->remove(self::HANDLER_NAME_AFTER_REAL_CALL);
    $config['handler']->push($handlerAfterRealCall, self::HANDLER_NAME_AFTER_REAL_CALL);

    $config['handler']->remove(self::HANDLER_NAME_BEFORE_REAL_CALL);
    $config['handler']->unshift($handlerBeforeRealCall, self::HANDLER_NAME_BEFORE_REAL_CALL);

    $config['handler']->remove(self::HANDLER_NAME_CUSTOM);
    if ($this->stubHandlerCustom) {
      $config['handler']->unshift($this->stubHandlerCustom, self::HANDLER_NAME_CUSTOM);
    }

    $config['handler']->remove(self::HANDLER_NAME_CUSTOM_RESPONSES_STACK);
    $config['handler']->unshift($handlerCustomResponsesStack, self::HANDLER_NAME_CUSTOM_RESPONSES_STACK);

    return $config;
  }

  /**
   * Checks if a stored response exists for the given request.
   *
   * @param \GuzzleHttp\Psr7\Request $request
   *   The request object to check for a stored response.
   *
   * @return bool
   *   TRUE if a stored response exists, FALSE otherwise.
   */
  public function stubHasStoredResponse(Request $request): bool {
    $hash = $this->stubGetRequestHash($request);
    $file = $this->stubGetRequestFilename($hash);
    return file_exists($file);
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
  public function stubGetStoredResponse(Request $request): Response {
    $hash = $this->stubGetRequestHash($request);
    $this->stubStoreRequestHashUsage($hash);
    try {
      $response = $this->stubGetStoredResponseByHash($hash);
    }
    catch (\Exception $e) {
      throw new \Exception(
        $e->getMessage()
        . " in the \"mock\" mode. Request: "
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
  public function stubGetStoredResponseByHash(string $hash): Response {
    $file = $this->stubGetRequestFilename($hash);

    try {
      // The `file_get_contents` throws a warning if the file doesn't exist,
      // so we have to do an additional check to get rid of this warning.
      // @todo Remove this exception when dropping PHPUnit 9 support.
      if (!file_exists($file)) {
        throw new \Exception("Missing the stored response file for the request with hash $hash - expected to find file $file.");
      }

      $body = file_get_contents($file);
      if ($body === FALSE) {
        throw new \Exception("Can't read the stored response file for the request with hash $hash - expected to find file $file.");
      }

      // The `file_get_contents` throws a warning if the file doesn't exist,
      // so we have to do an additional check to get rid of this warning.
      // @todo Remove this exception when dropping PHPUnit 9 support.
      $fileMetadata = $this->stubGetRequestFilename($hash, metadata: TRUE);
      if (!file_exists($fileMetadata)) {
        throw new \Exception("Missing the stored response metadata file for the request with hash $hash - expected to find file $file.");
      }
      $metadata = Yaml::decode(file_get_contents($fileMetadata));
      if ($metadata == FALSE) {
        throw new \Exception("Can't read the stored response metadata file for the request with hash $hash - expected to find file $fileMetadata.");
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
      if (!$this->ensureTestNameInMetadata($metadata)) {
        $this->stubSetStoredResponseMetadataByHash($metadata, $hash);
      }
      $this->stubLogResponseUsage($hash, 'read');
    }
    catch (\Exception $e) {
      $this->stubLogResponseUsage($hash, 'missing');
      throw $e;
    }

    $response = new Response(
      status: $status,
      headers: $headers,
      body: $body,
    );
    return $response;
  }

  /**
   * Get the stored response metadata from the storage by the request hash.
   *
   * @param string $hash
   *   A request hash.
   *
   * @return array
   *   The stored response metadata array.
   */
  public function stubGetStoredResponseMetadataByHash(string $hash): array {
    $fileMetadata = $this->stubGetRequestFilename($hash, metadata: TRUE);
    if (!$metadata = Yaml::decode(file_get_contents($fileMetadata))) {
      throw new \Exception("No stored metadata found for the hash \"$hash\" in the file " . $fileMetadata);
    }
    return $metadata;
  }

  /**
   * Stores the stored response metadata by the request hash.
   *
   * @param array $metadata
   *   The metadata to set.
   * @param string $hash
   *   A request hash.
   */
  public function stubSetStoredResponseMetadataByHash(array $metadata, string $hash): void {
    $fileMetadata = $this->stubGetRequestFilename($hash, metadata: TRUE);
    if (file_exists($fileMetadata)) {
      $storedContent = file_get_contents($fileMetadata);
      if ($storedContent == Yaml::encode($metadata)) {
        return;
      }
    }
    file_put_contents($fileMetadata, Yaml::encode($metadata));
  }

  /**
   * Deletes the stored response files from the storage by the request hash.
   *
   * @param string $hash
   *   A request hash.
   */
  public function stubDeleteStoredResponseByHash(string $hash): void {
    unlink($this->stubGetRequestFilename($hash));
    unlink($this->stubGetRequestFilename($hash, metadata: TRUE));
  }

  /**
   * Gets the current test name, or generates it if not set.
   *
   * @return string
   *   The current test name.
   */
  public function stubGetTestName(): string {
    return $this->testName;
  }

  /**
   * Sets the test name, autodetect the name if not provided.
   *
   * @param string|null $name
   *   The test name. If NULL - tries to autodetect it.
   */
  public function stubSetTestName(?string $name = NULL): void {
    if ($name !== NULL) {
      $this->testName = $name;
    }
    elseif ($this->stubIsPhpunitTest()) {
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
   * Sets the context value to use for generating the stored responses hash.
   *
   * @param string|null $context
   *   The context value. Use NULL to reset the context.
   */
  public function stubSetContext(?string $context = NULL): void {
    $this->stubContext = $context;
  }

  /**
   * Gets the current context value used in generating responses hash.
   *
   * @return string|null
   *   The context value.
   */
  public function stubGetContext(): ?string {
    return $this->stubContext;
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
  public function stubGetRequestMockMode(): ?string {
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
   *   A mode: store, mock, append or NULL to use the Drupal default mode.
   */
  public function stubSetRequestMockMode(string $mode): void {
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
  public function stubStoreResponse(Response $response, ?Request $request = NULL, ?string $hash = NULL) {
    $hash ??= $this->stubGetRequestHash($request);
    $filename = $this->stubGetRequestFilename($hash);
    $body = $response->getBody();
    $body->rewind();
    $content = $body->getContents();
    // Restore the seek to the beginning of the stream.
    $body->rewind();

    // Additional checks for the usage log mode.
    if ($this->options[self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE]) {
      if (file_exists($filename)) {
        $storedContent = file_get_contents($filename);
        if ($storedContent == $content) {
          $usageOperation = 'check';
        }
        else {
          $usageOperation = 'update';
        }
      }
      else {
        $usageOperation = 'create';
      }
    }

    file_put_contents($filename, $content);

    $metadataFilename = $this->stubGetRequestFilename($hash, metadata: TRUE);
    $metadata = $this->prepareMetadata($request, $response);
    if (file_exists($metadataFilename)) {
      $metadataStoredContent = file_get_contents($metadataFilename);
      $metadataStored = Yaml::decode($metadataStoredContent);
      $metadata['tests'] = $metadataStored['tests'];
      // On the stubSetStoredResponse we have no request data, so copying it
      // from the stored response metadata.
      if (
        !isset($metadata['request'])
        && isset($metadataStored['request'])
      ) {
        $metadata['request'] = $metadataStored['request'];
      }
    }

    $this->ensureTestNameInMetadata($metadata);
    $this->stubSetStoredResponseMetadataByHash($metadata, $hash);
    if (isset($usageOperation)) {
      $this->stubLogResponseUsage($hash, $usageOperation);
    }
    $this->stubStoreRequestHashUsage($hash);
  }

  /**
   * Prepares the metadata array.
   *
   * @param \GuzzleHttp\Psr7\Request|null $request
   *   The request.
   * @param \GuzzleHttp\Psr7\Response $response
   *   The response.
   */
  protected function prepareMetadata(?Request $request, Response $response): array {
    $metadata = [
      'tests' => [],
      'response' => [
        'status' => $response->getStatusCode(),
      ],
    ];
    if ($this->options[self::OPTION_STORE_HEADERS]) {
      $metadata['response']['headers'] = $response->getHeaders();
      if ($this->options[self::OPTION_STORE_HEADERS_SKIP_KEYS]) {
        foreach ($this->options[self::OPTION_STORE_HEADERS_SKIP_KEYS] ?? [] as $header) {
          unset($metadata['response']['headers'][$header]);
        }
      }
    }
    if ($request) {
      $metadata['request'] = $this->stubGetRequestMetadata($request);
    }
    return $metadata;
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
  public function stubGetRequestFilenameFromRequest(Request $request, bool $metadata = FALSE): string {
    return $this->stubGetRequestFilename($this->stubGetRequestHash($request), $metadata);
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
  public function stubGetRequestFilename(string $hash, bool $metadata = FALSE): string {
    $directory = $this->stubGetResponsesStorageDirectory();
    $filename = $metadata ?
      "$directory/$hash.metadata.yml" :
      "$directory/$hash.txt";
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
  protected function stubGetRequestMetadata($request): array {
    $metadata = [
      'method' => $request->getMethod(),
      'uri' => $request->getUri()->__toString(),
    ];
    if ($this->stubContext) {
      $metadata['context'] = $this->stubContext;
    }
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
  public function stubGetRequestHash($request): string {
    return md5(json_encode($this->stubGetRequestMetadata($request)));
  }

  /**
   * Checks if the current test using PHPUnit.
   *
   * @return bool
   *   True if PHPUnit, false otherwise.
   */
  protected function stubIsPhpunitTest(): bool {
    return defined('PHPUNIT_COMPOSER_INSTALL');
  }

  /**
   * Gets the URI regular expression.
   *
   * @return string|null
   *   The URI regular expression.
   */
  public function stubGetUriRegexp(): ?string {
    return $this->options[self::OPTION_URI_REGEXP];
  }

  /**
   * Sets the URI regular expression.
   *
   * @param string|null $regexp
   *   The URI regular expression.
   */
  public function stubSetUriRegexp(?string $regexp): void {
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
  protected function stubMatchRequest(Request $request): bool {
    if ($this->options[self::OPTION_URI_REGEXP] ?? NULL) {
      return preg_match($this->options[self::OPTION_URI_REGEXP], $request->getUri()->__toString());
    }
    return TRUE;
  }

  /**
   * Returns the current responses storage directory.
   */
  public function stubGetResponsesStorageDirectory(): string {
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
  public function stubSetResponsesStorageDirectory(string $directory): void {
    $this->responsesStorageDirectory = $directory;
  }

  /**
   * Adds the request hash to the container.
   *
   * @param string $hash
   *   A hash value.
   */
  protected function stubStoreRequestHashUsage(string $hash): void {
    $this->handledRequests[] = $hash;
  }

  /**
   * Returns the list of hashes of all mocked responses.
   *
   * @return array
   *   A list of hashes of all mocked responses.
   */
  public function stubGetHandledRequests(): array {
    return $this->handledRequests;
  }

  /**
   * Returns the last request response body.
   *
   * @return string
   *   The last response body.
   */
  public function stubGetLastResponse(int $delta = 0): string {
    $hashes = array_reverse($this->stubGetHandledRequests());
    return $this->stubGetStoredResponseByHash($hashes[$delta])->getBody()->getContents();
  }

  /**
   * Logs the response usage to the log file.
   *
   * @param string $hash
   *   A hash of the request.
   * @param string $operation
   *   The operation type: 'read', 'create', 'update', 'check', 'missing'.
   */
  private function stubLogResponseUsage(string $hash, string $operation): void {
    if (!$this->options[self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE]) {
      return;
    }
    $entry = [
      "time" => microtime(TRUE),
      "hash" => $hash,
      "operation" => $operation,
      "test" => $this->stubGetTestName(),
    ];
    file_put_contents($this->options[self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE], json_encode($entry) . "\n", FILE_APPEND);
  }

  /**
   * Ensures that the test name is present in the metadata.
   *
   * @param array $metadata
   *   The metadata array to check.
   *
   * @return bool
   *   TRUE if the test name is already present, FALSE otherwise.
   */
  protected function ensureTestNameInMetadata(array &$metadata) {
    $testName = $this->stubGetTestName();
    $metadata['tests'] ??= [];
    if (in_array($testName, $metadata['tests'])) {
      return TRUE;
    }
    $metadata['tests'][] = $testName;
    sort($metadata['tests']);
    return FALSE;
  }

  /**
   * Retrieves the response usage log entries from the log file as array.
   *
   * @return array
   *   The array of log entries.
   */
  public function stubGetResponseUsageLog(): array {
    if (
      empty($this->options[self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE])
      || !file_exists($this->options[self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE])
    ) {
      return [];
    }
    $log = file_get_contents($this->options[self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE]);
    $entries = explode("\n", $log);
    $result = [];
    foreach ($entries as $entry) {
      if ($entry) {
        $result[] = json_decode($entry, TRUE);
      }
    }
    return $result;
  }

  /**
   * Removes the response usage log file.
   */
  public function stubRemoveResponseUsageLog(): void {
    unlink($this->options[self::OPTION_LOG_STORED_RESPONSES_USAGE_FILE]);
  }

  /**
   * Adds a custom response to the stack.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   A response to add.
   */
  public function stubAddCustomResponseToStack(ResponseInterface $response) {
    $this->stubCustomResponsesStack[] = $response;
  }

  /**
   * Sets a custom handler for the HTTP client.
   *
   * @param callable|null $handler
   *   A custom handler or null to unset the handler.
   */
  public function stubSetCustomHandler(?callable $handler) {
    $this->stubHandlerCustom = $handler;
    // Additionally reset the handler in the last handler stack, if set.
    if ($this->stubHandlerStackLast) {
      $this->stubHandlerStackLast->remove(self::HANDLER_NAME_CUSTOM);
      if ($handler) {
        $this->stubHandlerStackLast->unshift($this->stubHandlerCustom, self::HANDLER_NAME_CUSTOM);
      }
    }
  }

}
