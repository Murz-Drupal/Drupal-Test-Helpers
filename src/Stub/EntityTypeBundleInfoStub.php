<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A stub of the Drupal's default EntityTypeBundleInfo class.
 */
class EntityTypeBundleInfoStub extends EntityTypeBundleInfo {

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
