<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A stub of the Drupal's default EntityTypeBundleInfo class.
 */
class EntityTypeBundleInfoStub extends EntityTypeBundleInfo {

  public function __construct() {
    $this->bundleInfo = [];
  }

  public function getAllBundleInfo() {
    return $this->bundleInfo;
  }

  public function stubAddBundleInfo(string $entityTypeId, string $bundleName, array $bundleInfo = NULL, $force = FALSE) {
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
