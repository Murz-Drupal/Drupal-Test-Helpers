<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default EntityFieldManager class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class EntityFieldManagerStub extends EntityFieldManager {

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
   * Sets field definitions for an entity type and a bundle.
   *
   * @param string $entityTypeId
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fieldDefinitions
   *   The definitions.
   * @param string $langcode
   *   The langcode, gets the current language if NULL.
   */
  public function stubSetFieldDefinitons(string $entityTypeId, string $bundle, array $fieldDefinitions, string $langcode = NULL): void {
    $langcode ??= TestHelpers::service('language_manager')->getCurrentLanguage()->getId();
    $this->fieldDefinitions[$entityTypeId][$bundle][$langcode] = $fieldDefinitions;
  }

  /**
   * Clears stored field definitions for an entity type and.
   *
   * @param string $entityTypeId
   *   The entity type id.
   */
  public function stubClearFieldDefinitons(string $entityTypeId): void {
    $this->fieldDefinitions[$entityTypeId] = [];
  }

  /**
   * Adds a field definition for an entity type and a bundle.
   *
   * @param string $entityTypeId
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $fieldName
   *   The field name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definitions.
   * @param string $langcode
   *   The langcode, gets the current language if NULL.
   */
  public function stubAddFieldDefiniton(string $entityTypeId, string $bundle, string $fieldName, FieldDefinitionInterface $fieldDefinition, string $langcode = NULL): void {
    $langcode ??= TestHelpers::service('language_manager')->getCurrentLanguage()->getId();
    $this->fieldDefinitions[$entityTypeId][$bundle][$langcode][$fieldName] = $fieldDefinition;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildBundleFieldDefinitions($entity_type_id, $bundle, array $base_field_definitions) {
    // This storage is required for function ::buildBundleFieldDefinitions().
    TestHelpers::getEntityStorage(BaseFieldOverride::class);
    $langcode = TestHelpers::service('language_manager')->getCurrentLanguage()->getId();
    return $this->fieldDefinitions[$entity_type_id][$bundle][$langcode] ?? [];
  }

}
