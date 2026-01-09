<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of Drupal's default ModuleHandler class.
 *
 * @package TestHelpers\DrupalServiceStubs
 */
class ModuleHandlerStub extends ModuleHandler {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct(
    $root,
    ?array $module_list,
    ?KeyValueFactoryInterface $keyValueFactory,
    ?CallableResolver $callableResolver,
    ?CacheBackendInterface $cache,
  ) {
    $root ??= TestHelpers::getDrupalRoot();
    $module_list ??= [];
    $keyValueFactory ??= TestHelpers::createMock(KeyValueFactoryInterface::class);
    $callableResolver ??= TestHelpers::createMock(CallableResolver::class);
    $cache ??= new NullBackend(self::class);

    parent::__construct(
      $root,
      $module_list,
      $keyValueFactory,
      $callableResolver,
      $cache,
    );
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
