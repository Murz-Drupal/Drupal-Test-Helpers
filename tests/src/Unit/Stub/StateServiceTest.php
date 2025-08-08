<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the State service from core in the unit tests context.
 */
#[CoversClass(State::class)]
#[Group('test_helpers')]
#[CoversMethod(State::class, '__construct')]
#[CoversMethod(State::class, 'set')]
#[CoversMethod(State::class, 'get')]
#[CoversMethod(State::class, 'getMultiple')]
#[CoversMethod(State::class, 'delete')]
class StateServiceTest extends UnitTestCase {

  /**
   * Tests the State service methods.
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
