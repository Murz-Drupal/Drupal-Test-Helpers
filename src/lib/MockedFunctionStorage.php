<?php

namespace Drupal\test_helpers\lib;

/**
 * A class for storing a mocked function context.
 *
 * Used in TestHelpers::mockPhpFunctionStorage().
 */
class MockedFunctionStorage {
  /**
   * A flag indicates that the function should be unmocked.
   *
   * @var bool
   */
  public bool $isUnmocked = FALSE;

  /**
   * A callback function to execute instead of the native function.
   *
   * @var callable
   */
  public $callback;

  /**
   * A collector of the function calls.
   *
   * @var MockedFunctionCalls
   */
  public MockedFunctionCalls $calls;

  /**
   * The class constructor.
   */
  public function __construct() {
    $this->calls = new MockedFunctionCalls();
  }

}
