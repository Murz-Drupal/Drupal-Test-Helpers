<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepository;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default EntityFieldManager class.
 */
class EntityFieldManagerStub extends EntityFieldManager {

  /**
   * Constructs a new EntityFieldManagerStub.
   */
  public function __construct() {
    $this->languageManager = \Drupal::service('language_manager');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->moduleHandler = \Drupal::service('module_handler');
    $this->entityLastInstalledSchemaRepository = TestHelpers::createMock(EntityLastInstalledSchemaRepository::class);

    // This storage is required for function ::buildBundleFieldDefinitions().
    TestHelpers::getEntityStorage(BaseFieldOverride::class);
  }

  /**
   * Sets base field definitions.
   *
   * @param string $entityTypeId
   *   The entity type id.
   * @param mixed $baseFieldDefinitions
   *   The definitions.
   */
  public function stubSetBaseFieldDefinitons(string $entityTypeId, $baseFieldDefinitions): void {
    $this->baseFieldDefinitions[$entityTypeId] = $baseFieldDefinitions;
  }

  /**
   * Sets field definitions.
   *
   * @param string $entityTypeId
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param mixed $fieldDefinitions
   *   The definitions.
   */
  public function stubSetFieldDefinitons(string $entityTypeId, string $bundle, $fieldDefinitions): void {
    // @todo Get a proper langcode.
    $langcode = 'en';
    $this->baseFieldDefinitions[$entityTypeId][$bundle][$langcode] = $fieldDefinitions;
  }

}
