<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Http\ClientFactory;
use Drupal\test_helpers\Stub\HttpClientFactoryStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;

/**
 * Tests HttpClientFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers_http_client_mock\HttpClientFactoryStub
 * @group test_helpers
 * @group test_helpers_http_client
 */
class HttpClientFactoryStubTest extends UnitTestCase {

  const BASE_URI = "https://jsonplaceholder.typicode.com/";
  const REQUEST_PATH_GET = "todos/1";
  const REQUEST_PATH_POST = "posts";
  const RESPONSES_STORAGE_DIRECTORY = __DIR__ . '/../../../assets';

  /**
   * @covers ::__construct
   */
  public function testModes() {
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
    $options = [];
    $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $factoryDefault2->getTestName());

    $clientDefault1 = $factoryDefault1->fromOptions($options);
    $clientStore = $factoryStore->fromOptions($options);
    $clientMock = $factoryMock->fromOptions($options);
    $clientDefault2 = $factoryDefault2->fromOptions($options);

    $findNamedHandlerInClient = function (Client $client, string $name): ?int {
      $options = TestHelpers::getPrivateProperty($client, 'config');
      $handler = $options['handler'];
      $stack = TestHelpers::getPrivateProperty($handler, 'stack');
      foreach ($stack as $delta => $item) {
        if ($item[1] == $name) {
          return $delta;
        }
      }
      return NULL;
    };

    $this->assertNull($findNamedHandlerInClient($clientDefault1, HttpClientFactoryStub::HANDLER_NAME_STORE));
    $this->assertNull($findNamedHandlerInClient($clientDefault1, HttpClientFactoryStub::HANDLER_NAME_MOCK));

    $this->assertNotNull($findNamedHandlerInClient($clientStore, HttpClientFactoryStub::HANDLER_NAME_STORE));
    $this->assertNull($findNamedHandlerInClient($clientStore, HttpClientFactoryStub::HANDLER_NAME_MOCK));

    $this->assertNull($findNamedHandlerInClient($clientMock, HttpClientFactoryStub::HANDLER_NAME_STORE));
    $this->assertNotNull($findNamedHandlerInClient($clientMock, HttpClientFactoryStub::HANDLER_NAME_MOCK));

    $this->assertNull($findNamedHandlerInClient($clientDefault2, HttpClientFactoryStub::HANDLER_NAME_STORE));
    $this->assertNull($findNamedHandlerInClient($clientDefault2, HttpClientFactoryStub::HANDLER_NAME_MOCK));
  }

  /**
   * @covers ::__construct
   * @covers ::fromOptions
   * @covers ::getStoredResponse
   * @covers ::isUseRealRequestsEnabled
   * @covers ::storeResponse
   * @covers ::getRequestHash
   */
  public function testStoringAndMockingRequests() {
    $request = new Request('GET', self::BASE_URI . self::REQUEST_PATH_GET);

    $httpClientFactoryStubStore = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );
    $clientFactoryStore = TestHelpers::service('http_client_factory', $httpClientFactoryStubStore, forceOverride: TRUE);
    $httpCallerStore = new HttpCaller($clientFactoryStore);

    $httpClientFactoryStubMock = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_MOCK,
    );
    $clientFactoryMock = TestHelpers::service('http_client_factory', $httpClientFactoryStubMock, forceOverride: TRUE);
    $httpCallerMock = new HttpCaller($clientFactoryMock);

    // Test a real response.
    $response = $httpCallerStore->get(self::REQUEST_PATH_GET);
    $result = json_decode($response->getBody()->getContents());
    $this->assertEquals(1, $result->userId);

    // Test a stored response.
    $response = $httpCallerStore->get(self::REQUEST_PATH_GET);
    $result = json_decode($response->getBody()->getContents());
    $this->assertEquals(1, $result->userId);

    // Test the modified stored response.
    $storedResponseHash = $httpClientFactoryStubMock->getRequestHash($request);
    $storedResponseFile = self::RESPONSES_STORAGE_DIRECTORY . '/' . $storedResponseHash . '.json';
    $storedResponseContents = file_get_contents($storedResponseFile);
    $modifiedResponse = json_decode($storedResponseContents);
    $modifiedResponse->userId = 42;
    file_put_contents($storedResponseFile, json_encode($modifiedResponse));

    // Check that the stored response has the modified value.
    $response = $httpCallerMock->get(self::REQUEST_PATH_GET);
    $result = json_decode($response->getBody()->getContents());
    $this->assertEquals(42, $result->userId);

    // Check that the real response has the original value.
    $response = $httpCallerStore->get(self::REQUEST_PATH_GET);
    $result = json_decode($response->getBody()->getContents());
    $this->assertEquals(1, $result->userId);

    // Restore the stored file contents.
    file_put_contents($storedResponseFile, json_encode($storedResponseContents));
  }

  /**
   * @covers ::getTestName
   * @covers ::setTestName
   * @covers ::storeResponse
   */
  public function testTestName() {
    $testName = __CLASS__ . '::' . __FUNCTION__;
    $testNameCustom = __CLASS__ . '::' . __FUNCTION__ . '_custom_name';

    $httpClientFactoryStub = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );
    $httpCaller1 = new HttpCaller($httpClientFactoryStub);

    $httpClientFactoryStubCustomName = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
      testName: $testNameCustom,
    );
    $httpCaller2 = new HttpCaller($httpClientFactoryStubCustomName);

    $request = new Request('GET', self::BASE_URI . self::REQUEST_PATH_GET);
    $httpCaller1->get(self::REQUEST_PATH_GET);
    $httpCaller2->get(self::REQUEST_PATH_GET);

    $storedResponseMetadataFile = $httpClientFactoryStub->getRequestFilenameFromRequest($request, TRUE);
    $storedResponseMetadata = json_decode(file_get_contents($storedResponseMetadataFile), TRUE);
    $this->assertContains($testName, $storedResponseMetadata['tests']);
    $this->assertContains($testNameCustom, $storedResponseMetadata['tests']);
    $httpClientFactoryStub->deleteStoredResponseByHash($httpClientFactoryStub->getRequestHash($request));

  }

  /**
   * @covers ::getRequestMetadata
   * @covers ::deleteStoredResponseByHash
   */
  public function testGetRequestMetadata() {
    $httpClientFactoryStub = new HttpClientFactoryStub(
      responsesStorageDirectory: self::RESPONSES_STORAGE_DIRECTORY,
      requestMockMode: HttpClientFactoryStub::HTTP_CLIENT_MODE_STORE,
    );

    $httpCaller = new HttpCaller($httpClientFactoryStub);

    $body = json_encode(['foo' => 'bar']);
    $uri = self::BASE_URI . self::REQUEST_PATH_POST;
    $request = new Request('POST', $uri, [], $body);
    $httpCaller->post(self::REQUEST_PATH_POST, $body);

    $storedResponseFile = $httpClientFactoryStub->getRequestFilenameFromRequest($request);
    $storedResponseMetadataFile = $httpClientFactoryStub->getRequestFilenameFromRequest($request, TRUE);
    $storedResponseMetadata = json_decode(file_get_contents($storedResponseMetadataFile), TRUE);
    $this->assertEquals('POST', $storedResponseMetadata['request']['method']);
    $this->assertEquals($uri, $storedResponseMetadata['request']['uri']);
    $this->assertEquals($body, $storedResponseMetadata['request']['body']);
    $this->assertTrue(file_exists($storedResponseFile));
    $httpClientFactoryStub->deleteStoredResponseByHash($httpClientFactoryStub->getRequestHash($request));
    $this->assertFalse(file_exists($storedResponseFile));
    $this->assertFalse(file_exists($storedResponseMetadataFile));
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
   */
  public function __construct(
    protected ClientFactory $httpClientFactory,
  ) {
    $this->client = $this->httpClientFactory->fromOptions([
      'base_uri' => HttpClientFactoryStubTest::BASE_URI,
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
