<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\test_helpers\UnitTestHelpers;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A stub of the Drupal's default ConfigFactory class.
 */
class ConfigFactoryStub extends ConfigFactory {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    StorageInterface $storage = NULL,
    EventDispatcherInterface $event_dispatcher = NULL,
    TypedConfigManagerInterface $typed_config = NULL
  ) {
    $this->storage = $storage ?? new MemoryStorage();
    // Workaround for the issue
    // https://www.drupal.org/project/drupal/issues/3325571.
    $this->storage->write('__config_factory_stub_placeholder', []);
    $this->eventDispatcher = $event_dispatcher ?? new ContainerAwareEventDispatcher(\Drupal::getContainer());
    $this->typedConfigManager = $typed_config ?? UnitTestHelpers::createMock(TypedConfigManager::class);
  }

  public function stubSetConfig($name, $data, $immutable = NULL) {
    $config = $this->getEditable($name);
    $config->setData($data);
    $key = $this->getConfigCacheKey($name, FALSE);
    $keyImmutable = $this->getConfigCacheKey($name, TRUE);
    // @todo Split to separate immutable and non-immutable sets.
    $this->cache[$key] = $config;
    $this->cache[$keyImmutable] = $config;
  }

}
