<?php

namespace Drupal\test_helpers;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * The Entity Storage Stub.
 */
class FieldTypeManagerStub extends UnitTestCase {

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

    $fieldTypePluginManager = UnitTestHelpers::addToContainer('plugin.manager.field.field_type', $this->createMock(FieldTypePluginManagerInterface::class));

    $fieldTypePluginManager
      ->method('getDefinitions')
      ->willReturnCallback(function () {
        return $this->definitions;
      });

    $fieldTypePluginManager
      ->method('getDefinition')
      ->willReturnCallback(function ($type) {
        return $this->definitions[$type];
      });

    $fieldTypePluginManager
      ->method('getDefaultStorageSettings')
      ->willReturnCallback(function ($type) {
        return $this->definitions[$type]['storage_settings'] ?? [];
      });

    $fieldTypePluginManager
      ->method('getDefaultFieldSettings')
      ->willReturnCallback(function ($type) {
        return $this->definitions[$type]['field_settings'] ?? [];
      });

    $fieldTypePluginManager
      ->method('createFieldItem')
      ->willReturnCallback(function ($items, $index, $values) {
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
      });
  }

  /**
   * Adds the definition, to the static storage.
   */
  public function addDefinition(string $fieldType, $definition = []) {
    if (!isset($definition['id'])) {
      $definition['id'] = $fieldType;
    }
    $this->definitions[$fieldType] = $definition;
  }

  /**
   * Defines the mapping of list class with field item class.
   */
  public function defineFieldItemClassByListClass(string $listClass, string $itemClass) {
    $this->fieldItemClassByListClassMap[$listClass] = $itemClass;
  }

}
