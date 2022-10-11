<?php

namespace Drupal\test_helpers\Stubs;

use Consolidation\AnnotatedCommand\Cache\NullCache;
use Drupal\Core\Extension\ModuleHandler;

/**
 * The TypedDataManagerStubFactory class.
 */
class ModuleHandlerStub extends ModuleHandler {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct() {
    $this->moduleList = [];
    $this->cacheBackend = new NullCache();
  }

}
