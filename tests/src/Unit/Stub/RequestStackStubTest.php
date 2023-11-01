<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests UrlGeneratorStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\RequestStackStub
 * @group test_helpers
 */
class RequestStackStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::push
   */
  public function testToString() {
    $service = TestHelpers::service('request_stack');

    $this->assertEquals(TestHelpers::REQUEST_STUB_DEFAULT_URI, $service->getCurrentRequest()->getUri());

    // The 'getMainRequest' method is missing in Symfony for Drupal 9.x.
    if (method_exists($service, 'getMainRequest')) {
      $this->assertEquals(TestHelpers::REQUEST_STUB_DEFAULT_URI, $service->getMainRequest()->getUri());
    }

    $uri1 = 'https://example.com/some-path';
    $request1 = Request::create($uri1);
    $service->push($request1);
    $this->assertEquals($uri1, $service->getCurrentRequest()->getUri());
    if (method_exists($service, 'getMainRequest')) {
      $this->assertEquals($uri1, $service->getMainRequest()->getUri());
    }

    $uri2 = 'https://example.com/some-another-path';
    $request2 = Request::create($uri2);
    $service->push($request2);
    $this->assertEquals($uri2, $service->getCurrentRequest()->getUri());
    if (method_exists($service, 'getMainRequest')) {
      $this->assertEquals($uri1, $service->getMainRequest()->getUri());
    }
  }

}
