<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Url;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests UrlGeneratorStub class.
 *
 * @coversDefaultClass \Drupal\Core\Routing\UrlGenerator
 * @group test_helpers
 */
class UrlGeneratorStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::toString
   */
  public function testToString() {
    TestHelpers::service('url_generator');

    $uriAbsolute = 'http://example.com/some-page';
    $url = Url::fromUri($uriAbsolute);
    $resolved = $url->toString();
    $this->assertEquals($uriAbsolute, $resolved);

    $path1 = '/some-path';
    $url = Url::fromUri('base:' . $path1);
    $resolved = $url->toString();
    $this->assertEquals($path1, $resolved);

    $path2 = '/some-path2';
    $url = Url::fromUserInput($path2);
    $resolved = $url->toString();
    $this->assertEquals($path2, $resolved);

    $path3 = '/some-path3';
    // @todo Try to get rid of this initialization.
    TestHelpers::service('router.no_access_checks');
    TestHelpers::service('router.route_provider')->stubAddRouteByPath('my-route', $path3);
    $request = Request::create('http://example.com' . $path3);
    $request->attributes->set('foo', 'bar');
    $url = Url::createFromRequest($request);
    $resolved = $url->toString();
    $this->assertEquals($path3, $resolved);
  }

}
