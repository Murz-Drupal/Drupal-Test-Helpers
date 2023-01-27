<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;

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
   * {@inheritDoc}
   */
  public function createFieldItem(FieldItemListInterface $items, $index, $values = NULL) {
    if ($items->getFieldDefinition()->getItemDefinition()) {
      $itemClass = $items->getFieldDefinition()->getItemDefinition()->getClass();
    }
    foreach ($this->fieldItemClassByListClassMap as $listClass => $itemClassCandidate) {
      if ($items instanceof $listClass) {
        $itemClass = $itemClassCandidate;
        break;
      }
    }

    $fieldItemDefinition = $items->getFieldDefinition()->getItemDefinition();

    // Using field item definition, if exists.
    if (is_object($fieldItemDefinition)) {
      $fieldItemDefinition->setClass($itemClass);
      $fieldItem = new $itemClass($fieldItemDefinition);
    }

    // If field definition is not defined, creating a mock for it.
    // @todo Make it better.
    else {
      $propertyDefinitions['value'] = $this->createMock(DataDefinitionInterface::class);
      $propertyDefinitions['value']->expects($this->any())
        ->method('isComputed')
        ->willReturn(FALSE);

      $fieldDefinition = $this->createMock(BaseFieldDefinition::class);
      $fieldDefinition->expects($this->any())
        ->method('getPropertyDefinitions')
        ->willReturn($this->returnValue($propertyDefinitions));

      $fieldInstanceDefinition = $this->createMock(FieldItemDataDefinitionInterface::class);
      $fieldInstanceDefinition->expects($this->any())
        ->method('getPropertyDefinitions')
        ->willReturn($this->returnValue($propertyDefinitions));
      $fieldInstanceDefinition->expects($this->any())
        ->method('getFieldDefinition')
        ->willReturn($this->returnValue($fieldDefinition));

      $fieldItem = new $itemClass($fieldInstanceDefinition);
    }

    // Applying the value to the field item.
    $fieldItem->setValue($values);

    return $fieldItem;
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
