<?php

namespace Drupal\Tests\test_helpers\Unit\Assets;

/**
 * A helper class to test protected class functions with a static class.
 */
class StaticClassWithProtectedItemsStub {

  /**
   * Disables creating instances of the class.
   */
  private function __construct() {
  }

  /**
   * Private Property 1.
   *
   * @var string
   */
  private static $propertyOne = 'propertyOneValue';

  /**
   * A private function for testing.
   *
   * @return string
   *   Returns 'function1' string.
   */
  private static function functionOne(): string {
    return 'functionOneResult';
  }

}
