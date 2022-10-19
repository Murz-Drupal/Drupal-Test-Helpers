<?php

namespace Drupal\Tests\test_helpers\Unit;

/**
 * A helper class to test utilities for protected class items.
 */
class ClassWithProtectedItemsStub {
  protected string $property1;

  protected $property2;

  protected const STATIC1 = 'Static const';

  public function __construct() {
    $this->property1 = 'foo';
  }

  public function getProperty1() {
    return $this->property1;
  }

  private function getProperty2() {
    return $this->property2;
  }

  private function getPropertyByName($name) {
    return $this->$name;
  }

}
