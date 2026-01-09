<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\CacheFactoryStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests CacheFactoryStub class.
 *
 * @covers CacheFactoryStub::__construct
 */
#[CoversClass(CacheFactoryStub::class)]
#[Group('test_helpers')]
class CacheFactoryStubTest extends UnitTestCase {

  /**
   * Tests the CacheFactoryStub service.
   */
  public function testService() {
    $cacheStatic = TestHelpers::service('cache.static', NULL, NULL, NULL, NULL, TRUE);
    $cacheConfig = TestHelpers::service('cache.config');
    $cacheDefault = TestHelpers::service('cache.default');

    $cacheStatic->set('foo', 'bar');
    $cacheConfig->set('qux', 'quux');
    $cacheDefault->set('corge', 'grault');
    $this->assertEquals($cacheStatic->get('foo')->data, 'bar');
    $this->assertEquals($cacheConfig->get('qux')->data, 'quux');
    $this->assertEquals($cacheDefault->get('corge')->data, 'grault');

    $cacheStatic->set('foo', 'baz');
    $this->assertEquals($cacheStatic->get('foo')->data, 'baz');

    $cacheStatic->delete('foo');
    $this->assertEquals($cacheStatic->get('foo'), FALSE);
  }

}
