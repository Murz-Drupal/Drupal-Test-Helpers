<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Http\ClientFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\Stub\HttpClientFactoryStub;
use Drupal\test_helpers\TestHelpers;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use donatj\MockWebServer\MockWebServer;

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
    $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $factoryDefault1->getTestName());

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
    $this->assertEquals($customTestName, $factoryStore->getTestName());

    $factoryMock = TestHelpers::service(
      'http_client_factory',
      customArguments: [
        $stack,
        HttpClientFactoryStub::HTTP_CLIENT_MODE_MOCK,
        self::RESPONSES_STORAGE_DIRECTORY,
      ],
      forceOverride: TRUE,
    );
    $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $factoryMock->getTestName());

    $factoryDefault2 = TestHelpers::service(
      'http_client_factory',
      forceOverride: TRUE);
    $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $factoryDefault2->getTestName());
  }

  /**
   * @covers ::__construct
   * @covers ::fromOptions
   * @covers ::getStoredResponse
   * @covers ::storeResponse
   * @covers ::getRequestHash
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

    $getResponse = function ($httpCaller, $requestPath) {
      $response = $httpCaller->get($requestPath);
      $result = json_decode($response->getBody()->getContents());
      return $result;
    };

    // Test a real response.
    $this->assertEquals('foobar', $getResponse($httpCallerStore, $requestPath)->_GET->get);

    // Test a stored response.
    $this->assertEquals('foobar', $getResponse($httpCallerStore, $requestPath)->_GET->get);

    // Store the modified stored response.
    $storedResponseHash = $httpClientFactoryStubMock::getRequestHash($request);
    $storedResponseFile = self::RESPONSES_STORAGE_DIRECTORY . '/' . $storedResponseHash . '.json';
    $storedResponseContents = file_get_contents($storedResponseFile);
    $modifiedResponse = json_decode($storedResponseContents);
    $modifiedResponse->_GET->get = 'baz';
    file_put_contents($storedResponseFile, json_encode($modifiedResponse));

    // Check that the stored response has the modified value.
    $this->assertEquals('baz', $getResponse($httpCallerMock, $requestPath)->_GET->get);

    // Check that the none mode returns the original value.
    $this->assertEquals('foobar', $getResponse($httpCallerNone, $requestPath)->_GET->get);

    // Check that the append mode has the modified value.
    $this->assertEquals('baz', $getResponse($httpCallerAppend, $requestPath)->_GET->get);

    // Check that the store mode provides the original value.
    $this->assertEquals('foobar', $getResponse($httpCallerStore, $requestPath)->_GET->get);

    // Check that the append mode started to return the modified value after
    // storing the actual request.
    $this->assertEquals('foobar', $getResponse($httpCallerAppend, $requestPath)->_GET->get);

    // Check the exception with the mock mode if the stored response is missing.
    $httpClientFactoryStubMock->deleteStoredResponseByHash($storedResponseHash);
    try {
      $httpCallerMock->get($requestPath);
      $this->fail('The call in the mock mode with the deleted response should throw an exception.');
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('Missing the stored response file', $e->getMessage());
      $this->assertStringContainsString($storedResponseHash, $e->getMessage());
    }

    // Check that the append more recreates the file if missing.
    $this->assertEquals('foobar', $getResponse($httpCallerAppend, $requestPath)->_GET->get);
    $this->assertEquals('foobar', $getResponse($httpCallerMock, $requestPath)->_GET->get);

    // Restore the stored file contents.
    \Drupal::service('http_client_factory')->deleteStoredResponseByHash($storedResponseHash);
    $server->stop();
  }

  /**
   * @covers ::getTestName
   * @covers ::setTestName
   * @covers ::storeResponse
   */
  public function testTestName() {
    $server = new MockWebServer();
    $server->start();
    $requestPath = '/endpoint?get=foobar';
    $baseUri = $server->getServerRoot();
    $url = $baseUri . $requestPath;

    $testName = __CLASS__ . '::' . __FUNCTION__;
    $testNameCustom = __CLASS__ . '::' . __FUNCTION__ . '_custom_name';

    $httpClientFactoryStub = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );
    $httpCaller1 = new HttpCaller($httpClientFactoryStub, $baseUri);

    $httpClientFactoryStubCustomName = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
      testName: $testNameCustom,
    );
    $httpCaller2 = new HttpCaller($httpClientFactoryStubCustomName, $baseUri);

    $request = new Request('GET', $url);
    $httpCaller1->get($requestPath);
    $httpCaller2->get($requestPath);

    $storedResponseMetadataFile = $httpClientFactoryStub->getRequestFilenameFromRequest($request, TRUE);
    $storedResponseMetadata = json_decode(file_get_contents($storedResponseMetadataFile), TRUE);
    $this->assertContains($testName, $storedResponseMetadata['tests']);
    $this->assertContains($testNameCustom, $storedResponseMetadata['tests']);

    $httpClientFactoryStub->deleteStoredResponseByHash($httpClientFactoryStub::getRequestHash($request));
    $server->stop();
  }

  /**
   * @covers ::getRequestMetadata
   * @covers ::deleteStoredResponseByHash
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

    $storedResponseFile = $httpClientFactoryStub->getRequestFilenameFromRequest($request);
    $storedResponseMetadataFile = $httpClientFactoryStub->getRequestFilenameFromRequest($request, TRUE);
    $storedResponseMetadata = json_decode(file_get_contents($storedResponseMetadataFile), TRUE);
    $this->assertEquals('POST', $storedResponseMetadata['request']['method']);
    $this->assertEquals($url, $storedResponseMetadata['request']['uri']);
    $this->assertEquals($body, $storedResponseMetadata['request']['body']);
    $this->assertTrue(file_exists($storedResponseFile));
    $httpClientFactoryStub->deleteStoredResponseByHash($httpClientFactoryStub::getRequestHash($request));
    $this->assertFalse(file_exists($storedResponseFile));
    $this->assertFalse(file_exists($storedResponseMetadataFile));
    $server->stop();
  }

  /**
   * @covers ::logResponseUsage
   * @covers ::removeResponseUsageLog
   * @covers ::getResponseUsageLog
   * @covers ::getMockedRequestsHashesContainer
   * @covers ::getLastResponse
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
    $this->assertEmpty($httpClientFactoryNoLog->getResponseUsageLog());
    $this->assertEmpty($httpClientFactoryLog->getResponseUsageLog());

    // Modify the first request stored response.
    $responseFake = new Response(202, [], 'fake response');
    $httpClientFactoryNoLog->storeResponse($responseFake, NULL, $hash1);

    TestHelpers::service('http_client_factory', $httpClientFactoryLog, forceOverride: TRUE);
    \Drupal::service('http_client_factory')->setRequestMockMode(HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE);
    $hash1 = $this->makeRequestGetHash($server, '/endpoint?get=test1');
    $hash2 = $this->testResponsesUsageLogSubFunction($httpClientFactoryLog, $server, '/endpoint?get=test2');
    \Drupal::service('http_client_factory')->setTestName();
    \Drupal::service('http_client_factory')->setRequestMockMode(HttpClientFactoryStub::HTTP_CLIENT_MODE_APPEND);
    // Calling the no-logging service.
    TestHelpers::service('http_client_factory', $httpClientFactoryNoLog, forceOverride: TRUE);
    $this->makeRequestGetHash($server, '/endpoint?get=test2');
    // Reverting to the logging service.
    TestHelpers::service('http_client_factory', $httpClientFactoryLog, forceOverride: TRUE);
    $this->makeRequestGetHash($server, '/endpoint?get=test2');
    $hash3 = $this->makeRequestGetHash($server, '/endpoint?get=test3');

    // Expect tat the no-log log is empty.
    $this->assertEmpty($httpClientFactoryNoLog->getResponseUsageLog());

    // Tests the responses container.
    $responsesContainer = $httpClientFactoryLog->getMockedRequestsHashesContainer();
    $this->assertEquals([
      $hash1,
      $hash2,
      $hash2,
      $hash3,
    ], $responsesContainer);

    $lastResponse0 = json_decode($httpClientFactoryLog->getLastResponse());
    $this->assertEquals('test3', $lastResponse0->_GET->get);
    $lastResponse1 = json_decode($httpClientFactoryLog->getLastResponse(1));
    $this->assertEquals('test2', $lastResponse1->_GET->get);

    $httpClientFactoryLog->deleteStoredResponseByHash($hash1);
    $httpClientFactoryLog->deleteStoredResponseByHash($hash2);
    $httpClientFactoryLog->deleteStoredResponseByHash($hash3);

    $log = $httpClientFactoryLog->getResponseUsageLog();
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
    $client = \Drupal::service('http_client_factory')->fromOptions([
      'base_uri' => $baseUri,
    ]);
    $client->send($request);
    $hash = HttpClientFactoryStub::getRequestHash($request);
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
    $httpClientFactory->setTestName();
    return $this->makeRequestGetHash($server, $path);
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
