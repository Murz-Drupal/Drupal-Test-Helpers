<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\test_helpers\lib\ConfigFactoryStubCacheInvalidator;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * A stub of the Drupal's default ConfigFactory class.
 */
class ConfigFactoryStub extends ConfigFactory {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    StorageInterface $storage,
    EventDispatcherInterface $event_dispatcher,
    TypedConfigManagerInterface $typed_config
  ) {
    // Workaround for the issue
    // https://www.drupal.org/project/drupal/issues/3325571.
    // $storage->write('__config_factory_stub_placeholder', []);.
    $invalidator = TestHelpers::service('cache_tags.invalidator');
    $configFactoryStubCacheInvalidator = new ConfigFactoryStubCacheInvalidator();
    $invalidator->addInvalidator($configFactoryStubCacheInvalidator);
    parent::__construct($storage, $event_dispatcher, $typed_config);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE) {
    // Now the static cache clearing is based on events (onConfigSave,
    // onConfigDelete), that are not working in Unit Tests context, so to
    // workaround just forces clearing the cache.
    // @todo Invent a better way to do this.
    $this->clearStaticCache();
    return parent::doLoadMultiple($names, $immutable);
  }

  /**
   * Sets a config value.
   *
   * @param string $name
   *   The name of the config.
   * @param array|string $dataOrYamlFile
   *   An array with a data to store, or a relative path to a yaml file.
   */
  public function stubSetConfig(string $name, $dataOrYamlFile): void {
    if (is_string($dataOrYamlFile)) {
      $filePath = TestHelpers::getModuleFilePath($dataOrYamlFile, 1);
      $data = Yaml::parseFile($filePath);
    }
    elseif (is_array($dataOrYamlFile)) {
      $data = $dataOrYamlFile;
    }
    else {
      throw new \Exception('The $dataOrYamlFile should be an array or a path to a YAML file.');
    }
    $this->storage->write($name, $data);
    $this->clearStaticCache();
  }

}
