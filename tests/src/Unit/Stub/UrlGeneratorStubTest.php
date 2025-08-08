<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Url;
use Drupal\test_helpers\Stub\UrlGeneratorStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests UrlGeneratorStub class.
 */
#[CoversClass(UrlGeneratorStub::class)]
#[Group('test_helpers')]
#[CoversMethod(UrlGeneratorStub::class, '__construct')]
class UrlGeneratorStubTest extends UnitTestCase {

  /**
   * Tests the toString method of UrlGeneratorStub.
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
