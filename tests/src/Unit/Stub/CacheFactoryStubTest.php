<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests CacheFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\CacheFactoryStub
 * @group test_helpers
 */
class CacheFactoryStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::set
   * @covers ::get
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
