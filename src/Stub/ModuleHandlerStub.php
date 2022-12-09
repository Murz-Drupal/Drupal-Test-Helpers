<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\ModuleHandler;

/**
 * A stub of the Drupal's default ModuleHandler class.
 */
class ModuleHandlerStub extends ModuleHandler {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct() {
    $this->moduleList = [];
    $this->cacheBackend = new NullBackend('test_helpers');
  }

}
