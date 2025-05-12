<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response as MockWebServerResponse;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Http\ClientFactory;
use Drupal\test_helpers\Stub\HttpClientFactoryStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Tests HttpClientFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\HttpClientFactoryStub
 * @group test_helpers
 * @group test_helpers_http_client
 */
class HttpClientFactoryStubTest extends UnitTestCase {

  const RESPONSES_STORAGE_DIRECTORY = __DIR__ . '/../../../assets';

  /**
   * @covers ::__construct
   */
  public function testConstructor() {
    $stack = HandlerStack::create();
    $customTestName = 'customNameOne';
    $factoryDefault1 = TestHelpers::service('http_client_factory');
    $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $factoryDefault1->stubGetTestName());

    $factoryStore = TestHelpers::service(
      'http_client_factory',
      customArguments: [
        $stack,
        HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
        self::RESPONSES_STORAGE_DIRECTORY,
        $customTestName,
      ],
      forceOverride: TRUE,
    );
    $this->assertEquals($customTestName, $factoryStore->stubGetTestName());

    $factoryMock = TestHelpers::service(
      'http_client_factory',
      customArguments: [
        $stack,
        HttpClientFactoryStub::HTTP_CLIENT_MODE_MOCK,
        self::RESPONSES_STORAGE_DIRECTORY,
      ],
      forceOverride: TRUE,
    );
    $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $factoryMock->stubGetTestName());

    $factoryDefault2 = TestHelpers::service(
      'http_client_factory',
      forceOverride: TRUE);
    $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $factoryDefault2->stubGetTestName());
  }

  /**
   * @covers ::__construct
   * @covers ::fromOptions
   * @covers ::stubGetStoredResponse
   * @covers ::stubStoreResponse
   * @covers ::stubGetRequestHash
   * @covers ::stubAddCustomResponseToStack
   */
  public function testStoringAndMockingRequests() {
    $server = new MockWebServer();
    $server->start();
    $requestPath = '/endpoint?get=foobar';
    $baseUri = $server->getServerRoot();
    $url = $baseUri . $requestPath;

    $request = new Request('GET', $url);

    $httpClientFactoryStubNone = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: NULL,
    );
    $clientFactoryNone = TestHelpers::service('http_client_factory', $httpClientFactoryStubNone, forceOverride: TRUE);
    $httpCallerNone = new HttpCaller($clientFactoryNone, $baseUri);

    $httpClientFactoryStubStore = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );
    $clientFactoryStore = TestHelpers::service('http_client_factory', $httpClientFactoryStubStore, forceOverride: TRUE);
    $httpCallerStore = new HttpCaller($clientFactoryStore, $baseUri);

    $httpClientFactoryStubMock = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_MOCK,
    );
    $clientFactoryMock = TestHelpers::service('http_client_factory', $httpClientFactoryStubMock, forceOverride: TRUE);
    $httpCallerMock = new HttpCaller($clientFactoryMock, $baseUri);

    $httpClientFactoryStubAppend = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_APPEND,
    );
    $clientFactoryAppend = TestHelpers::service('http_client_factory', $httpClientFactoryStubAppend, forceOverride: TRUE);
    $httpCallerAppend = new HttpCaller($clientFactoryAppend, $baseUri);

    // Test a real response.
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerStore, $requestPath)->_GET->get);

    // Test a stored response.
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerStore, $requestPath)->_GET->get);

    // Store the modified stored response.
    $storedResponseHash = $httpClientFactoryStubMock->stubGetRequestHash($request);
    $storedResponseFile = self::RESPONSES_STORAGE_DIRECTORY . '/' . $storedResponseHash . '.txt';
    $storedResponseContents = file_get_contents($storedResponseFile);
    $modifiedResponse = json_decode($storedResponseContents);
    $modifiedResponse->_GET->get = 'baz';
    file_put_contents($storedResponseFile, json_encode($modifiedResponse));

    // Check that the stored response has the modified value.
    $this->assertEquals('baz', $this->makeRequestGetJsonResponse($httpCallerMock, $requestPath)->_GET->get);
    // Check that the none mode returns the original value.
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerNone, $requestPath)->_GET->get);
    // Check that the append mode has the modified value.
    $this->assertEquals('baz', $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath)->_GET->get);
    // Check that the store mode provides the original value and restores the
    // file for the append mode.
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerStore, $requestPath)->_GET->get);
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath)->_GET->get);

    // Test the stubAddCustomResponseToStack().
    // And the stubSetCustomHandler().
    $clientFactoryAppend->stubAddCustomResponseToStack(
      new Response(404, [], '{"value":"AppendTest1"}'),
    );
    $clientFactoryAppend->stubAddCustomResponseToStack(
      new Response(404, [], '{"value":"AppendTest2"}'),
    );
    $clientFactoryStore->stubAddCustomResponseToStack(
      new Response(404, [], '{"value":"StoreTest1"}'),
    );
    $this->assertEquals('AppendTest1', $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath)->value);
    $this->assertEquals('AppendTest2', $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath)->value);
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath)->_GET->get);
    $this->assertEquals('StoreTest1', $this->makeRequestGetJsonResponse($httpCallerStore, $requestPath)->value);
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerStore, $requestPath)->_GET->get);

    // Check that the append mode started to return the modified value after
    // storing the actual request.
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath)->_GET->get);

    // Check the exception with the mock mode if the stored response is missing.
    $httpClientFactoryStubMock->stubDeleteStoredResponseByHash($storedResponseHash);
    try {
      $httpCallerMock->get($requestPath);
      $this->fail('The call in the mock mode with the deleted response should throw an exception.');
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('Missing the stored response file', $e->getMessage());
      $this->assertStringContainsString($storedResponseHash, $e->getMessage());
    }

    // Check that the append more recreates the file if missing.
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath)->_GET->get);
    $this->assertEquals('foobar', $this->makeRequestGetJsonResponse($httpCallerMock, $requestPath)->_GET->get);

    // Restore the stored file contents.
    \Drupal::service('http_client_factory')->stubDeleteStoredResponseByHash($storedResponseHash);
    $server->stop();
  }

  /**
   * @covers ::__construct
   * @covers ::fromOptions
   * @covers ::stubGetStoredResponse
   * @covers ::stubStoreResponse
   */
  public function testStoringSameRequestWithContext() {
    $server = new MockWebServer();
    $server->start();
    $requestPath = '/testStoringSameRequestWithContext-endpoint';
    $baseUri = $server->getServerRoot();
    $url = $baseUri . $requestPath;

    $httpClientFactoryStubStore = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );
    $clientFactoryStore = TestHelpers::service('http_client_factory', $httpClientFactoryStubStore, forceOverride: TRUE);
    $httpCallerStore = new HttpCaller($clientFactoryStore, $baseUri);

    $httpClientFactoryStubMock = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_MOCK,
    );
    $clientFactoryMock = TestHelpers::service('http_client_factory', $httpClientFactoryStubMock, forceOverride: TRUE);
    $httpCallerMock = new HttpCaller($clientFactoryMock, $baseUri);

    $httpClientFactoryStubAppend = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_APPEND,
    );
    $clientFactoryAppend = TestHelpers::service('http_client_factory', $httpClientFactoryStubAppend, forceOverride: TRUE);
    $httpCallerAppend = new HttpCaller($clientFactoryAppend, $baseUri);

    $responseMocked1 = new MockWebServerResponse('{"value":"r1"}');
    $responseMocked2 = new MockWebServerResponse('{"value":"r2"}');
    $responseMocked3 = new MockWebServerResponse('{"value":"r3"}');

    $server->setResponseOfPath($requestPath, $responseMocked1);
    $response = $this->makeRequestGetJsonResponse($httpCallerStore, $requestPath);
    $this->assertEquals('r1', $response->value);

    $server->setResponseOfPath($requestPath, $responseMocked2);
    $response = $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath);
    $this->assertEquals('r1', $response->value);
    $response = $this->makeRequestGetJsonResponse($httpCallerMock, $requestPath);
    $this->assertEquals('r1', $response->value);
    $response = $this->makeRequestGetJsonResponse($httpCallerStore, $requestPath);
    $this->assertEquals('r2', $response->value);
    $response = $this->makeRequestGetJsonResponse($httpCallerMock, $requestPath);
    $this->assertEquals('r2', $response->value);

    $server->setResponseOfPath($requestPath, $responseMocked3);
    $clientFactoryAppend->stubSetContext('context1');
    $response = $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath);
    $this->assertEquals('r3', $response->value);
    $clientFactoryAppend->stubSetContext();
    $response = $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath);
    $this->assertEquals('r2', $response->value);

    $response = $this->makeRequestGetJsonResponse($httpCallerAppend, $requestPath);
    $this->assertEquals('r2', $response->value);

    $storedRequestHashes = array_unique([
      ...$clientFactoryStore->stubGetHandledRequests(),
      ...$clientFactoryAppend->stubGetHandledRequests(),
    ]);
    foreach ($storedRequestHashes as $hash) {
      $clientFactoryStore->stubDeleteStoredResponseByHash($hash);
    }

  }

  /**
   * @covers ::stubSetCustomHandler
   */
  public function testStubSetCustomHandler() {
    $server = new MockWebServer();
    $server->start();
    $requestPath = '/endpoint?get=foobar';
    $baseUri = $server->getServerRoot();

    $httpClientFactory = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_MOCK,
    );

    $clientFactory = TestHelpers::service('http_client_factory', $httpClientFactory, forceOverride: TRUE);
    $customHandler1 = function (callable $handler) {
      return function ($request, array $options) {
        $response = new Response(200, [], 'CustomHandler1');
        return new FulfilledPromise($response);
      };
    };
    $customHandler2 = function (callable $handler) {
      return function ($request, array $options) {
        $response = new Response(200, [], 'CustomHandler2');
        return new FulfilledPromise($response);
      };
    };
    $clientFactory->stubSetCustomHandler($customHandler1);

    $httpCaller = new HttpCaller($clientFactory, $baseUri);
    $this->assertEquals('CustomHandler1', $httpCaller->get($requestPath)->getBody()->getContents());
    $this->assertEquals('CustomHandler1', $httpCaller->get($requestPath)->getBody()->getContents());
    $clientFactory->stubAddCustomResponseToStack(new Response(404, [], 'Cr1'));
    $clientFactory->stubAddCustomResponseToStack(new Response(301, [], 'Cr2'));
    $this->assertEquals('Cr1', $httpCaller->get($requestPath)->getBody()->getContents());
    $this->assertEquals('Cr2', $httpCaller->get($requestPath)->getBody()->getContents());
    $this->assertEquals('CustomHandler1', $httpCaller->get($requestPath)->getBody()->getContents());
    $clientFactory->stubSetCustomHandler($customHandler2);
    $this->assertEquals('CustomHandler2', $httpCaller->get($requestPath)->getBody()->getContents());
    $clientFactory->stubSetCustomHandler(NULL);
    // This call should throw an exception because in the HTTP_CLIENT_MODE_MOCK
    // the file is missing.
    TestHelpers::assertException(
      function () use ($httpCaller, $requestPath) {
        $this->assertEquals('CustomHandler', $httpCaller->get($requestPath)->getBody()->getContents());
      }
    );
    $clientFactory->stubAddCustomResponseToStack(new Response(404, [], 'Cr1'));
    $this->assertEquals('Cr1', $httpCaller->get($requestPath)->getBody()->getContents());
    // This call should throw an exception because in the HTTP_CLIENT_MODE_MOCK
    // the file is missing.
    TestHelpers::assertException(
      function () use ($httpCaller, $requestPath) {
        $this->assertEquals('CustomHandler', $httpCaller->get($requestPath)->getBody()->getContents());
      }
    );
  }

  /**
   * @covers ::stubGetTestName
   * @covers ::stubSetTestName
   * @covers ::stubStoreResponse
   */
  public function testTestName() {
    $server = new MockWebServer();
    $server->start();
    $requestPath = '/endpoint?get=foobar';
    $baseUri = $server->getServerRoot();
    $url = $baseUri . $requestPath;

    $testName = __CLASS__ . '::' . __FUNCTION__;
    $testNameCustom2 = __CLASS__ . '::' . __FUNCTION__ . '_42';
    $testNameCustom3 = __CLASS__ . '::' . __FUNCTION__ . '_32';

    $httpClientFactoryStub = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );
    $httpCaller1 = new HttpCaller($httpClientFactoryStub, $baseUri);

    $httpClientFactoryStubCustomName2 = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
      testName: $testNameCustom2,
    );
    $httpCaller2 = new HttpCaller($httpClientFactoryStubCustomName2, $baseUri);

    $httpClientFactoryStubCustomName3 = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
      testName: $testNameCustom3,
    );
    $httpCaller3 = new HttpCaller($httpClientFactoryStubCustomName3, $baseUri);

    $request = new Request('GET', $url);
    $httpCaller1->get($requestPath);
    $httpCaller2->get($requestPath);
    $httpCaller3->get($requestPath);

    $storedResponseMetadataFile = $httpClientFactoryStub->stubGetRequestFilenameFromRequest($request, TRUE);
    $storedResponseMetadata = Yaml::decode(file_get_contents($storedResponseMetadataFile));
    // Check the sorted order of tests.
    $this->assertEquals($testName, $storedResponseMetadata['tests'][0]);
    $this->assertEquals($testNameCustom2, $storedResponseMetadata['tests'][2]);
    $this->assertEquals($testNameCustom3, $storedResponseMetadata['tests'][1]);

    $httpClientFactoryStub->stubDeleteStoredResponseByHash($httpClientFactoryStub->stubGetRequestHash($request));
    $server->stop();
  }

  /**
   * @covers ::stubGetRequestMetadata
   * @covers ::stubDeleteStoredResponseByHash
   */
  public function testGetRequestMetadata() {
    $server = new MockWebServer();
    $server->start();
    $requestPath = '/endpoint?get=foobar';
    $baseUri = $server->getServerRoot();
    $url = $baseUri . $requestPath;

    $httpClientFactoryStub = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );

    $httpCaller = new HttpCaller($httpClientFactoryStub, $baseUri);

    $body = json_encode(['foo' => 'bar']);
    $request = new Request('POST', $url, [], $body);
    $httpCaller->post($requestPath, $body);

    $storedResponseFile = $httpClientFactoryStub->stubGetRequestFilenameFromRequest($request);
    $storedResponseMetadataFile = $httpClientFactoryStub->stubGetRequestFilenameFromRequest($request, TRUE);
    $storedResponseMetadata = Yaml::decode(file_get_contents($storedResponseMetadataFile));
    $this->assertEquals('POST', $storedResponseMetadata['request']['method']);
    $this->assertEquals($url, $storedResponseMetadata['request']['uri']);
    $this->assertEquals($body, $storedResponseMetadata['request']['body']);
    $this->assertTrue(file_exists($storedResponseFile));
    $httpClientFactoryStub->stubDeleteStoredResponseByHash($httpClientFactoryStub->stubGetRequestHash($request));
    $this->assertFalse(file_exists($storedResponseFile));
    $this->assertFalse(file_exists($storedResponseMetadataFile));
    $server->stop();
  }

  /**
   * @covers ::stubLogResponseUsage
   * @covers ::stubRemoveResponseUsageLog
   * @covers ::stubGetResponseUsageLog
   * @covers ::stubGetHandledRequests
   * @covers ::stubGetLastResponse
   */
  public function testResponsesUsageLog() {
    $server = new MockWebServer();
    $server->start();

    $logFile = tempnam(sys_get_temp_dir(), 'test_helpers_http_client_log_');

    $httpClientFactoryLog = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_APPEND,
      options: [
        HttpClientFactoryStub::OPTION_LOG_STORED_RESPONSES_USAGE_FILE => $logFile,
      ],
    );

    $httpClientFactoryNoLog = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_APPEND,
      options: [
        HttpClientFactoryStub::OPTION_LOG_STORED_RESPONSES_USAGE_FILE => NULL,
      ],
    );

    // Make several requests without log mode enabled.
    TestHelpers::service('http_client_factory', $httpClientFactoryNoLog, forceOverride: TRUE);
    $hash1 = $this->makeRequestGetHash($server, '/endpoint?get=test1');
    $hash2 = $this->makeRequestGetHash($server, '/endpoint?get=test2');
    $this->assertEmpty($httpClientFactoryNoLog->stubGetResponseUsageLog());
    $this->assertEmpty($httpClientFactoryLog->stubGetResponseUsageLog());

    // Modify the first request stored response.
    $responseFake = new Response(202, [], 'fake response');
    $httpClientFactoryNoLog->stubStoreResponse($responseFake, NULL, $hash1);

    TestHelpers::service('http_client_factory', $httpClientFactoryLog, forceOverride: TRUE);
    \Drupal::service('http_client_factory')->stubSetRequestMockMode(HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE);
    $hash1 = $this->makeRequestGetHash($server, '/endpoint?get=test1');
    $hash2 = $this->testResponsesUsageLogSubFunction($httpClientFactoryLog, $server, '/endpoint?get=test2');
    \Drupal::service('http_client_factory')->stubSetTestName();
    \Drupal::service('http_client_factory')->stubSetRequestMockMode(HttpClientFactoryStub::HTTP_CLIENT_MODE_APPEND);
    // Calling the no-logging service.
    TestHelpers::service('http_client_factory', $httpClientFactoryNoLog, forceOverride: TRUE);
    $this->makeRequestGetHash($server, '/endpoint?get=test2');
    // Reverting to the logging service.
    TestHelpers::service('http_client_factory', $httpClientFactoryLog, forceOverride: TRUE);
    $this->makeRequestGetHash($server, '/endpoint?get=test2');
    $hash3 = $this->makeRequestGetHash($server, '/endpoint?get=test3');

    // Expect tat the no-log log is empty.
    $this->assertEmpty($httpClientFactoryNoLog->stubGetResponseUsageLog());

    // Tests the responses container.
    $responsesContainer = $httpClientFactoryLog->stubGetHandledRequests();
    $this->assertEquals([
      $hash1,
      $hash2,
      $hash2,
      $hash3,
    ], $responsesContainer);

    $lastResponse0 = json_decode($httpClientFactoryLog->stubGetLastResponse());
    $this->assertEquals('test3', $lastResponse0->_GET->get);
    $lastResponse1 = json_decode($httpClientFactoryLog->stubGetLastResponse(1));
    $this->assertEquals('test2', $lastResponse1->_GET->get);

    $httpClientFactoryLog->stubDeleteStoredResponseByHash($hash1);
    $httpClientFactoryLog->stubDeleteStoredResponseByHash($hash2);
    $httpClientFactoryLog->stubDeleteStoredResponseByHash($hash3);

    $log = $httpClientFactoryLog->stubGetResponseUsageLog();
    $logExpected = [
      [
        'hash' => $hash1,
        'operation' => 'update',
        'test' => self::class . '::testResponsesUsageLog',
      ],
      [
        'hash' => $hash2,
        'operation' => 'check',
        'test' => self::class . '::testResponsesUsageLogSubFunction',
      ],
      [
        'hash' => $hash2,
        'operation' => 'read',
        'test' => self::class . '::testResponsesUsageLog',
      ],
      [
        'hash' => $hash3,
        'operation' => 'create',
        'test' => self::class . '::testResponsesUsageLog',
      ],
    ];
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($log, $logExpected));
  }

  /**
   * Makes a request and returns the hash of the request.
   *
   * @param \donatj\MockWebServer\MockWebServer $server
   *   A mock web server.
   * @param string $path
   *   A path.
   * @param string $type
   *   A type of the request.
   * @param mixed $body
   *   A body of the request.
   *
   * @return string
   *   A hash of the request.
   */
  private function makeRequestGetHash(MockWebServer $server, string $path, $type = 'GET', $body = NULL): string {
    $baseUri = $server->getServerRoot();
    $url = $baseUri . $path;
    $request = new Request($type, $url, body: $body);
    $service = \Drupal::service('http_client_factory');
    $client = $service->fromOptions([
      'base_uri' => $baseUri,
    ]);
    $client->send($request);
    $hash = $service->stubGetRequestHash($request);
    return $hash;
  }

  /**
   * A sub-function to test the responses usage log.
   *
   * @param \Drupal\test_helpers\Stub\HttpClientFactoryStub $httpClientFactory
   *   An HTTP client factory.
   * @param \donatj\MockWebServer\MockWebServer $server
   *   A mock web server.
   * @param string $path
   *   A path.
   *
   * @return string
   *   A hash of the request.
   */
  private function testResponsesUsageLogSubFunction($httpClientFactory, $server, $path) {
    $httpClientFactory->stubSetTestName();
    return $this->makeRequestGetHash($server, $path);
  }

  /**
   * Makes a request and returns the JSON response.
   *
   * @param \Drupal\Tests\test_helpers\Unit\Stub\HttpCaller $httpCaller
   *   An HTTP caller.
   * @param string $requestPath
   *   A request path.
   *
   * @return mixed
   *   A JSON response.
   */
  private function makeRequestGetJsonResponse($httpCaller, $requestPath) {
    $response = $httpCaller->get($requestPath);
    $result = json_decode($response->getBody()->getContents());
    return $result;
  }

}

/**
 * A helper class to test the functionality.
 */
class HttpCaller {

  /**
   * An HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  public Client $client;

  /**
   * Constructs the HttpCaller class.
   *
   * @param \Drupal\Core\Http\ClientFactory $httpClientFactory
   *   An HTTP Client factory.
   * @param string $baseUri
   *   A base uri.
   */
  public function __construct(
    protected ClientFactory $httpClientFactory,
    protected $baseUri,
  ) {
    $this->client = $this->httpClientFactory->fromOptions([
      'base_uri' => $baseUri,
    ]);
  }

  /**
   * Gets the contents from an url.
   *
   * @param string $url
   *   An url.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response of the url.
   */
  public function get(string $url) {
    return $this->client->get($url);
  }

  /**
   * Makes a post requests  an url.
   *
   * @param string $url
   *   An url.
   * @param string $body
   *   The body contents.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response of the url.
   */
  public function post(string $url, string $body) {
    return $this->client->post($url, ['body' => $body]);
  }

}
