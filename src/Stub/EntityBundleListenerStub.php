<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityBundleListener;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default DateFormatter class.
 */
class EntityBundleListenerStub extends EntityBundleListener {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager = NULL,
    EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    EntityFieldManagerInterface $entity_field_manager = NULL,
    ModuleHandlerInterface $module_handler = NULL
  ) {
    $entity_type_manager ??= TestHelpers::service('entity_type.manager');
    $entity_type_bundle_info ??= TestHelpers::service('entity_type.bundle.info');
    $entity_field_manager ??= TestHelpers::service('entity_field.manager');
    $module_handler ??= TestHelpers::service('module_handler');
    parent::__construct($entity_type_manager, $entity_type_bundle_info, $entity_field_manager, $module_handler);
  }

}
