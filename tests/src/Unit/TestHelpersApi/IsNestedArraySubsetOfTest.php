<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests IsNestedArraySubsetOfTest API function.
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
#[CoversMethod(TestHelpers::class, 'isNestedArraySubsetOf')]
class IsNestedArraySubsetOfTest extends UnitTestCase {

  /**
   * Tests the isNestedArraySubsetOf function.
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
      ['key' => ['bar']],
    ));

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf(
      ['foo', ['bar' => ['baz' => 'qux', 'fred' => 'thud']]],
      [1 => ['bar' => ['baz' => 'qux']]],
    ));

  }

}
