<?php

namespace Drupal\test_helpers\lib;

/**
 * Singleton trait.
 */
trait SingletonTrait {

  /**
   * The class instance.
   *
   * @var static
   */
  private static $instance = NULL;

  /**
   * Gets the instance via lazy initialization (created on first usage).
   *
   * @return static
   */
  public static function getInstance() {
    if (!self::$instance) {
      $c = get_called_class();
      self::$instance = new $c(...func_get_args());
    }

    return self::$instance;
  }

  /**
   * The __clone function disabler.
   *
   * Prevents the instance from being cloned (which would create a second
   * instance of it).
   */
  private function __clone() {
  }

  /**
   * The __wakeup function disabler.
   *
   * Prevents from being unserialized (which would create a second instance
   * of it).
   */
  public function __wakeup() {
    throw new \Exception("Cannot unserialize singleton");
  }

}
