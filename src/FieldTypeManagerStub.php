<?php

namespace Drupal\test_helpers;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManager;
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

    $fieldTypePluginManagerNew = $this->createPartialMock(FieldTypePluginManager::class, [
      'getDefinitions',
      'getDefinition',
      'getDefaultStorageSettings',
      'getDefaultFieldSettings',
      'createFieldItem',

      /* Adds the definition, to the static storage. */
      'stubAddDefinition',
      /* Defines the mapping of list class with field item class. */
      'stubDefineFieldItemClassByListClass',
    ]);

    $fieldTypePluginManager = UnitTestHelpers::addToContainer('plugin.manager.field.field_type', $fieldTypePluginManagerNew);

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

    $fieldTypePluginManager
      ->method('stubDefineFieldItemClassByListClass')
      ->willReturnCallback(function (string $listClass, string $itemClass) {
        $this->fieldItemClassByListClassMap[$listClass] = $itemClass;
      });

    $fieldTypePluginManager
      ->method('stubAddDefinition')
      ->willReturnCallback(function (string $fieldType, $definition = []) {
        if (!isset($definition['id'])) {
          $definition['id'] = $fieldType;
        }
        $this->definitions[$fieldType] = $definition;
      });

  }

}
