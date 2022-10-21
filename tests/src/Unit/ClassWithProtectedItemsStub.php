<?php

namespace Drupal\Tests\test_helpers\Unit;

/**
 * A helper class to test utilities for protected class items.
 */
class ClassWithProtectedItemsStub {

  /**
   * Protected Property 1.
   *
   * @var string
   */
  protected string $property1;

  /**
   * Protected Property 2.
   *
   * @var string
   */
  protected $property2;

  /**
   * Protected const.
   *
   * @var const
   */
  protected const STATIC1 = 'Static const';

  /**
   * The Constructor.
   */
  public function __construct() {
    $this->property1 = 'foo';
  }

  /**
   * The getProperty1.
   */
  public function getProperty1() {
    return $this->property1;
  }

  /**
   * The getProperty2.
   */
  private function getProperty2() {
    return $this->property2;
  }

  /**
   * The getPropertyByName.
   */
  private function getPropertyByName($name) {
    return $this->$name;
  }

}
