<?php

namespace Drupal\Tests\test_helpers\Unit\utils;

use Drupal\test_helpers\TestHelpers;
use Drupal\test_helpers\utils\AssertSequence;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\test_helpers\utils\AssertSequence
 * @group test_helpers
 */
class AssertSequenceTest extends UnitTestCase {

  /**
   * A static instance to test in multiple functions.
   *
   * @var \Drupal\test_helpers\utils\AssertSequence
   */
  static protected $assertSequence;

  /**
   * @covers ::callEventSubscriber
   */
  public function testCallEventSubscriber() {
    // A positive scenario with correct sequence.
    self::$assertSequence = NULL;
    $sequence = [
      'value1',
      'value2',
      'value1',
      'value1',
      '333',
    ];
    $this->callAssertForList($sequence);
    self::$assertSequence->__destruct();

    // Wrong order of values.
    self::$assertSequence = NULL;
    $sequence = [
      'value1',
      'value1',
      'value2',
      'value1',
      '333',
    ];
    TestHelpers::assertException(function () use ($sequence) {
      $this->callAssertForList($sequence);
    });
    self::$assertSequence->__destruct();

    // Too many values.
    self::$assertSequence = NULL;
    $sequence = [
      'value1',
      'value2',
      'value1',
      'value1',
      '333',
      '333',
    ];
    TestHelpers::assertException(function () use ($sequence) {
      $this->callAssertForList($sequence);
    });
    self::$assertSequence->__destruct();

    // Not enough values.
    self::$assertSequence = NULL;
    $sequence = [
      'value1',
      'value2',
      'value1',
      'value1',
    ];
    $this->callAssertForList($sequence);
    $assertSequence = &self::$assertSequence;
    TestHelpers::assertException(function () use ($assertSequence) {
      $assertSequence->finalize();
    });
    // No exception should be thrown, because we finalized manually.
    $assertSequence->__destruct();
    TestHelpers::setPrivateProperty($assertSequence, 'isExceptionThrown', TRUE);
  }

  /**
   * Calls the assertion for a list.
   *
   * @param array $list
   *   A list of values.
   */
  private function callAssertForList(array $list): void {
    foreach ($list as $item) {
      $this->assertSequenceCallback($item);
    }
  }

  /**
   * Asserts a single value of a sequence.
   *
   * @param mixed $value
   *   A value to assert.
   */
  private function assertSequenceCallback($value): void {
    $expectedSequence = [
      'value1',
      'value2',
      'value1',
      'value1',
      333,
    ];
    if (empty(self::$assertSequence)) {
      self::$assertSequence = new AssertSequence($expectedSequence, 'myList1');
    }
    self::$assertSequence->assert($value);
  }

}
