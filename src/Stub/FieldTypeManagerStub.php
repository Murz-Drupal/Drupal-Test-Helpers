<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Field\FieldTypePluginManager;

/**
 * A stub of the Drupal's default FieldTypePluginManager class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 *
 * @phpstan-ignore-next-line We still need to alter the plugin declaration.
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
   *
   * No idea about the phpstan warning:
   * Missing cache backend declaration for performance.
   *
   * @todo Investigate this.
   * @phpstan-ignore-next-line
   */
  public function __construct() {
    $this->fieldItemClassByListClassMap = [];
    // @phpstan-ignore-next-line We need a static call here.
    $this->typedDataManager = \Drupal::service('typed_data_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getCachedDefinitions() {
    return $this->definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultStorageSettings($type) {
    return $this->definitions[$type]['storage_settings'] ?? [];
  }

  /**
   * {@inheritdoc}
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
