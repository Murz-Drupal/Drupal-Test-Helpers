<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Field\FieldTypePluginManager;

/**
 * A stub of the Drupal's default FieldTypePluginManager class.
 */
class FieldTypeManagerStub extends FieldTypePluginManager {

  /**
   * Static storage for defined definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Mapping of field item classes by list class.
   *
   * @var array
   */
  protected $fieldItemClassByListClassMap;

  /**
   * Constructs a new FieldTypeManagerStub.
   */
  public function __construct() {
    $this->fieldItemClassByListClassMap = [];
    $this->typedDataManager = \Drupal::service('typed_data_manager');
  }

  /**
   * {@inheritDoc}
   */
  public function getCachedDefinitions() {
    return $this->definitions;
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultStorageSettings($type) {
    return $this->definitions[$type]['storage_settings'] ?? [];
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultFieldSettings($type) {
    return $this->definitions[$type]['field_settings'] ?? [];
  }

  /**
   * Defines a field item class by lists class.
   *
   * @param string $listClass
   *   The list class.
   * @param string $itemClass
   *   The item class.
   */
  public function stubDefineFieldItemClassByListClass(string $listClass, string $itemClass): void {
    $this->fieldItemClassByListClassMap[$listClass] = $itemClass;
  }

  /**
   * Sets the definition for field type.
   *
   * @param string $fieldType
   *   The field type.
   * @param mixed $definition
   *   The definition, empty array by default.
   */
  public function stubSetDefinition(string $fieldType, $definition = []): void {
    if (!isset($definition['id'])) {
      $definition['id'] = $fieldType;
    }
    $this->definitions[$fieldType] = $definition;
  }

}
