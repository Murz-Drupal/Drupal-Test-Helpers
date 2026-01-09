<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\KeyValueFactoryStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the State service from core in the unit tests context.
 *
 * @covers KeyValueFactoryStub::__construct
 * @covers KeyValueFactoryStub::get
 */
#[CoversClass(KeyValueFactoryStub::class)]
#[Group('test_helpers')]
class KeyValueFactoryStubTest extends UnitTestCase {

  /**
   * Tests the KeyValueFactoryStub with the State service.
   */
  public function testStateService() {
    $keyvalue = TestHelpers::service('keyvalue');
    $storage = $keyvalue->get('test_storage');
    $storage->set('test_key', 'test_value');
    $storage->set('test.key2', 'test_value2');

    $this->assertEquals('test_value', $storage->get('test_key'));
    $this->assertEquals('test_value2', $storage->get('test.key2'));

    $storage->delete('test_key');
    $this->assertNull($storage->get('test_key'));
    $this->assertEquals('test_value2', $storage->get('test.key2'));
  }

}
