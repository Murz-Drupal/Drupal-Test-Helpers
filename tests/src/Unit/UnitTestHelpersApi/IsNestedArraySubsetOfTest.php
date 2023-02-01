<?php

namespace Drupal\Tests\test_helpers\Unit\UnitTestHelpersApi;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests IsNestedArraySubsetOfTest API function.
 *
 * @coversDefaultClass Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class IsNestedArraySubsetOfTest extends UnitTestCase {

  /**
   * @covers ::isNestedArraySubsetOf
   */
  public function testIsNestedArraySubsetOf() {
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf(
      ['foo', 'bar', 'baz'],
      ['foo', 'bar'],
    ));

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf(
      ['foo', 'bar', 'baz'],
      [1 => 'bar', 2 => 'baz'],
    ));
    // This should return false, because the index of values is different.
    $this->assertFalse(TestHelpers::isNestedArraySubsetOf(
      ['foo', 'bar'],
      ['bar', 'baz'],
    ));

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf(
      ['foo', ['bar', 'key' => 'baz']],
      [1 => ['bar']],
    ));

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf(
      ['foo', ['bar', 'key' => 'baz']],
      [1 => ['key' => 'baz']],
    ));

    $this->assertFalse(TestHelpers::isNestedArraySubsetOf(
      ['foo', ['bar', 'key' => 'baz']],
      [['baz']],
    ));

    $this->assertFalse(TestHelpers::isNestedArraySubsetOf(
      ['foo', ['bar', 'key' => 'baz']],
      ['key' => ['bazz']],
    ));

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf(
      ['foo', ['bar' => ['baz' => 'qux', 'fred' => 'thud']]],
      [1 => ['bar' => ['baz' => 'qux']]],
    ));

  }

}
