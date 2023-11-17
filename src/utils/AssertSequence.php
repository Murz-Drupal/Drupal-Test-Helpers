<?php

namespace Drupal\test_helpers\utils;

/**
 * Asserts that all values from a sequence were received in the right order.
 *
 * Usage example:
 * ```
 * $assertSequence = new AssertSequence(['Foo', 'Bar', 'Baz'], 'entityTitles');
 * TestHelpers::setMockedClassMethod(
 *   $controller,
 *   'buildListRow',
 *   function($entity) use ($assertSequence) {
 *     $assertSequence->assert($entity->title->value);
 *     return ['title' => $entity->title->value];
 *   }
 * );
 * $controller->articleList();
 * $assertSequence->finalize();
 * ```
 */
class AssertSequence {

  /**
   * A storage of the sequence values.
   *
   * @var array
   */
  private $values;

  /**
   * A name of the assert object.
   *
   * @var string
   */
  private $name;

  /**
   * A pointer to the next expected element in the sequence.
   *
   * @var int
   */
  private $nextItemDelta;

  /**
   * A pointer to the next expected element in the sequence in human variable.
   *
   * @var int
   */
  private $nextItemDeltaHuman;

  /**
   * A flag that an exception is thrown, to not exception again on __destruct.
   *
   * @var bool
   */
  private $isExceptionThrown = FALSE;

  /**
   * The constructor of the object.
   *
   * @param array $values
   *   An ordered array of values to assert.
   * @param string|null $name
   *   A name for the object, to display in exceptions.
   */
  public function __construct(array $values, string $name = NULL) {
    // Removing array keys, if exist.
    $this->values = array_values($values);
    $this->name = $name;
    $this->nextItemDelta = 0;
  }

  /**
   * Asserts that the current value matches the expected sequence of values.
   */
  public function assert($value) {
    $this->nextItemDelta + 1;
    if (!isset($this->values[$this->nextItemDelta])) {
      $count = count($this->values);
      $this->isExceptionThrown = TRUE;
      $deltaHuman = $this->nextItemDelta + 1;
      throw new \Exception("The value '$value' has serial number $deltaHuman, that exceeds the expected number of values ($count).");
    }
    $expectedValue = $this->values[$this->nextItemDelta];
    if ($expectedValue != $value) {
      $this->isExceptionThrown = TRUE;
      $deltaHuman = $this->nextItemDelta + 1;
      throw new \Exception("The value '$value' is not expected for the delta $deltaHuman, expected value: '$expectedValue'");
    }
    $this->nextItemDelta++;
    $this->nextItemDeltaHuman = $this->nextItemDelta + 1;
  }

  /**
   * Finalizes the assertion with checking that all the expected items received.
   */
  public function finalize() {
    // If we already thrown an exception, there are no needs to throw it again.
    if ($this->isExceptionThrown) {
      return;
    }
    $count = count($this->values);
    if ($this->nextItemDelta !== $count) {
      $nextKeyValue = $this->values[$this->nextItemDelta];
      $nameValue = $this->name ? " '$this->name'" : '';
      $actualReceivedCount = $this->nextItemDelta - 1;
      $this->isExceptionThrown = TRUE;
      throw new \Exception("Only $actualReceivedCount of $count items of the sequence$nameValue were received.\nNext missing value is '$nextKeyValue'.");
    }
  }

  /**
   * Calls the function finalize() to final check at the end of the execution.
   */
  public function __destruct() {
    $this->finalize();
  }

}
