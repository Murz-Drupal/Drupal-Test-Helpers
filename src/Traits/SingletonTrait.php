<?php

namespace Drupal\test_helpers\Traits;

/**
 * Singleton trait.
 */
trait SingletonTrait {
  private static $instance = NULL;

  /**
   * Gets the instance via lazy initialization (created on first usage)
   */
  public static function getInstance(): object {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * Is not allowed to call from outside to prevent from creating multiple
   * instances, to use the singleton, you have to obtain the instance from
   * Singleton::getInstance() instead.
   */
  private function __construct() {
  }

  /**
   * Prevents the instance from being cloned (which would create a second
   * instance of it).
   */
  private function __clone() {
  }

  /**
   * Prevents from being unserialized (which would create a second instance
   * of it).
   */
  public function __wakeup() {
    throw new \Exception("Cannot unserialize singleton");
  }

}
