<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the State service from core in the unit tests context.
 *
 * @coversDefaultClass \Drupal\Core\State\State
 * @group test_helpers
 */
class StateServiceTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::set
   * @covers ::get
   * @covers ::getMultiple
   * @covers ::delete
   */
  public function testStateService() {
    $state = TestHelpers::service('state');
    $state->set('test_key', 'test_value');
    $state->set('test.key2', 'test_value2');

    $this->assertEquals('test_value', $state->get('test_key'));
    $this->assertEquals('test_value2', $state->get('test.key2'));

    $this->assertEquals([
      "test_key" => "test_value",
      "test.key2" => "test_value2",
    ],
      $state->getMultiple([
        'test_key',
        'test.key2',
      ])
    );

    $state->set('test_key', 'test_value_overridden');
    $this->assertEquals('test_value_overridden', $state->get('test_key'));
    $this->assertEquals('test_value2', $state->get('test.key2'));

    $state->delete('test_key');
    $this->assertNull($state->get('test_key'));
    $this->assertEquals('test_value2', $state->get('test.key2'));
  }

}
