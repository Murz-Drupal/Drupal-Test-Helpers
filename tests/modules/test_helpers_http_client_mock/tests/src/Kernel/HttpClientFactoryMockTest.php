<?php

namespace Drupal\Tests\test_helpers_http_client_mock\Kernel;

use donatj\MockWebServer\MockWebServer;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Psr7\Request;

/**
 * @coversDefaultClass \Drupal\test_helpers_http_client_mock\HttpClientFactoryMock
 * @group test_helpers
 * @group test_helpers_http_client_mock
 */
class HttpClientFactoryMockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_helpers_test',
    'test_helpers_http_client_mock',
  ];

  /**
   * Tests articlesList() function.
   */
  public function testStoreHttpResponse() {
    $server = new MockWebServer();
    $server->start();
    $url = $server->getServerRoot() . '/endpoint?get=foobar';
    $request = new Request('GET', $url);

    $directory = __DIR__ . '/../../assets/testStoreHttpResponse';
    $service = \Drupal::service('http_client_factory');
    $service->setResponsesStorageDirectory($directory);
    $service->setRequestMockMode('store');
    $options = [];
    $clientStore = $service->fromOptions($options);
    $hash = $service->getRequestHash($request);
    $resultsStoreFile = $service->getRequestFilename($hash);

    // Deleting the file if exists, to check if it will be recreated.
    if (file_exists($resultsStoreFile)) {
      unlink($resultsStoreFile);
      unlink($service->getRequestFilename($hash, TRUE));
    }
    $responseStore = $clientStore->request('GET', $url);
    $resultStore = $responseStore->getBody()->getContents();
    $resultStored = file_get_contents($resultsStoreFile);
    $this->assertEquals($resultStore, $resultStored);

    // Writing a modified response data to the file and checks if it is read.
    $service->setRequestMockMode('mock');
    $clientMock = $service->fromOptions($options);
    $resultStoredArray = json_decode($resultStored, TRUE);
    $resultStoredArray['userId'] = 7;
    $resultStoredModified = json_encode($resultStoredArray);
    file_put_contents($resultsStoreFile, $resultStoredModified);
    $responseMock = $clientMock->request('GET', $url);
    $resultMocked = $responseMock->getBody()->getContents();
    $resultMockedArray = json_decode($resultMocked, TRUE);
    $this->assertEquals(7, $resultMockedArray['userId']);

    unlink($resultsStoreFile);
    unlink($service->getRequestFilename($hash, TRUE));
    rmdir($directory);
    $server->stop();
  }

}
