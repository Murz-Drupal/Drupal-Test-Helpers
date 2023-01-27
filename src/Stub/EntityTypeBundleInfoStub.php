<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A stub of the Drupal's default EntityTypeBundleInfo class.
 */
class EntityTypeBundleInfoStub extends EntityTypeBundleInfo {

  /**
   * The EntityTypeBundleInfoStub constructor.
   */
  public function __construct() {
    $this->bundleInfo = [];
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
   * @param array|null $bundleInfo
   *   The bundle info.
   * @param mixed $force
   *   Override already setted info.
   */
  public function stubSetBundleInfo(string $entityTypeId, string $bundleName, array $bundleInfo = NULL, $force = FALSE): void {
    if ($bundleInfo === NULL) {
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
