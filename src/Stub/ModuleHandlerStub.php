<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default ModuleHandler class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class ModuleHandlerStub extends ModuleHandler {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct() {
    $this->root = TestHelpers::getDrupalRoot();
    $this->moduleList = [];
    $this->cacheBackend = new NullBackend('test_helpers');
  }

}
