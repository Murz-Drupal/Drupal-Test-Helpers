<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\Extension;
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
    if (property_exists($this, 'eventDispatcher')) {
      $this->eventDispatcher = TestHelpers::createMock(EventDispatcherInterface::class);
    }
    // The cache backend is removed in Drupal 11.1.x+.
    // @todo Remove this when dropping support for Drupal 11.0.x.
    if (property_exists($this, 'cacheBackend')) {
      $this->cacheBackend = new NullBackend('test_helpers');
    }
  }

  /**
   * Adds a module to the stub instance module list.
   *
   * @param string $name
   *   The name of the module.
   * @param string $path
   *   The path to the module.
   */
  public function stubAddModule($name, $path) {
    $this->stubAdd('module', $name, $path);
  }

  /**
   * Adds a profile to the stub instance module list.
   *
   * @param string $name
   *   The name of the profile.
   * @param string $path
   *   The path to the profile.
   */
  public function stubAddProfile($name, $path) {
    $this->stubAdd('profile', $name, $path);
  }

  /**
   * Adds a module or profile to the stub instance module list.
   *
   * @param string $type
   *   The type of the extension ('module' or 'profile').
   * @param string $name
   *   The name of the extension.
   * @param string $path
   *   The path to the extension.
   */
  private function stubAdd($type, $name, $path) {
    $pathname = "$path/$name.info.yml";
    $php_file_path = $this->root . "/$path/$name.$type";
    $filename = file_exists($php_file_path) ? "$name.$type" : NULL;
    $this->moduleList[$name] = new Extension($this->root, $type, $pathname, $filename);
  }

}
