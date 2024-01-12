<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Config\DatabaseStorage;

/**
 * A stub of the Drupal's default Connection class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class DatabaseStorageStub extends DatabaseStorage {

  /**
   * A static storage for stored items.
   *
   * @var array
   */
  protected $stubStorage = [];

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return isset($this->stubStorage[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    return $this->stubStorage[$name] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = [];
    foreach ($names as $name) {
      $list[$name] = $this->read($name);
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    $this->stubStorage[$name] = $data;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    unset($this->stubStorage[$name]);
    // @todo Check, maybe return false.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    $this->stubStorage[$new_name] = $this->stubStorage[$name];
    unset($this->stubStorage[$name]);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $list = [];
    foreach ($this->stubStorage as $name => $data) {
      if (strpos($name, $prefix) === 0) {
        $list[] = $name;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    foreach ($this->stubStorage as $name => $data) {
      if (strpos($name, $prefix) === 0) {
        unset($this->stubStorage[$name]);
      }
    }
    return TRUE;
  }

}
