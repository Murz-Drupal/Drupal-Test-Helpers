<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default EntityTypeBundleInfo class.
 */
class EntityTypeBundleInfoStub extends EntityTypeBundleInfo {

  /**
   * The EntityTypeBundleInfoStub constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager = NULL,
    LanguageManagerInterface $language_manager = NULL,
    ModuleHandlerInterface $module_handler = NULL,
    TypedDataManagerInterface $typed_data_manager = NULL,
    CacheBackendInterface $cache_backend = NULL
  ) {
    $entity_type_manager ??= TestHelpers::service('entity_type.manager');
    $language_manager ??= TestHelpers::service('language_manager');
    $module_handler ??= TestHelpers::service('module_handler');
    $typed_data_manager ??= TestHelpers::service('typed_data_manager');
    $cache_backend ??= TestHelpers::service('cache.backend.memory')->get('cache_discovery');
    parent::__construct($entity_type_manager, $language_manager, $module_handler, $typed_data_manager, $cache_backend);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBundleInfo() {
    return $this->bundleInfo;
  }

  /**
   * Sets the bundle info.
   *
   * @param string $entityTypeId
   *   The entity type id.
   * @param string $bundleName
   *   The bundle name.
   * @param \Drupal\Core\Entity\EntityInterface|null $bundleEntity
   *   The bundle info.
   * @param mixed $force
   *   Override already setted info.
   */
  public function stubSetBundleInfo(string $entityTypeId, string $bundleName, EntityInterface $bundleEntity = NULL, $force = FALSE): void {
    if ($bundleEntity) {
      $bundleInfo = [
        'label' => new TranslatableMarkup('@bundleName bundle', ['@bundleName' => $bundleEntity->label()]),
        'translatable' => $bundleEntity instanceof TranslatableInterface,
      ];
    }
    else {
      $bundleInfo = [
        'label' => new TranslatableMarkup('@bundleName bundle', ['@bundleName' => $bundleName]),
        'translatable' => FALSE,
      ];
    }
    if ($force || !isset($this->bundleInfo[$entityTypeId][$bundleName])) {
      $this->bundleInfo[$entityTypeId][$bundleName] = $bundleInfo;
    }
  }

}
