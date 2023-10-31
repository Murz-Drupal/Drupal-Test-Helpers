<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityBundleListener;

/**
 * A stub of the Drupal's default DateFormatter class.
 */
class EntityBundleListenerStub extends EntityBundleListener {

  /**
   * {@inheritdoc}
   */
  public function onBundleCreate($bundle, $entity_type_id) {
    // @todo An ugly workaround, rework it.
    if (!$this->entityTypeManager->hasHandler($entity_type_id, 'storage')) {
      return;
    }
    parent::onBundleCreate($bundle, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleDelete($bundle, $entity_type_id) {
    // @todo An ugly workaround, rework it.
    $this->entityTypeBundleInfo->clearCachedBundles();
    if (!$this->entityTypeManager->hasHandler($entity_type_id, 'storage')) {
      return;
    }
    parent::onBundleDelete($bundle, $entity_type_id);
  }

}
