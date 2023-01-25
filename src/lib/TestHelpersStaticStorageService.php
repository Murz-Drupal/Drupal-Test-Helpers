<?php

namespace Drupal\test_helpers\lib;

use Drupal\Tests\UnitTestCase;

/**
 * A singleton class to provide UnitTestCase private functions as public.
 */
class TestHelpersStaticStorageService extends UnitTestCase {
  /**
   * Static storage.
   * @var array
   */
  protected array $storage;

  public function &get($name) {
    if (!isset($this->storage[$name])) {
      $this->storage[$name] = NULL;
    }
    return $this->storage[$name];
  }

}
