<?php

namespace Drupal\test_helpers\lib;

/**
 * A simple static storage.
 */
class TestHelpersStaticStorageService {
  /**
   * Static storage.
   *
   * @var array
   */
  protected array $storage;

  /**
   * Gets or creates a new static storage by name.
   *
   * @param string $name
   *   The service name.
   *
   * @return mixed
   *   The reference to static storage variable.
   */
  public function &get(string $name) {
    if (!isset($this->storage[$name])) {
      $this->storage[$name] = NULL;
    }
    return $this->storage[$name];
  }

}
