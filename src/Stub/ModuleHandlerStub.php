<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    // The cache backend is added only in Drupal 11.x+, check if it is present.
    if (isset($this->eventDispatcher)) {
      $this->eventDispatcher = TestHelpers::createMock(EventDispatcherInterface::class);
    }
    // The cache backend is removed in Drupal 11.1.x+.
    // @todo Remove this when dropping support for Drupal 11.0.x.
    if (property_exists($this, 'cacheBackend')) {
      $this->cacheBackend = new NullBackend('test_helpers');
    }
  }

}
