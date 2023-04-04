<?php

namespace Drupal\test_helpers\StubFactory;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\test_helpers\Plugin\Field\FieldType\ItemStubItem;
use Drupal\test_helpers\TestHelpers;
use Drupal\user\Entity\User;

/**
 * A factory for creating stubs of entities.
 */
class EntityStubFactory {

  /**
   * Disables the constructor to use only static methods.
   */
  private function __construct() {
  }

  /**
   * Creates an entity stub with field values.
   *
   * @param string $entityTypeNameOrClass
   *   A full path to an entity type class, or an entity type id for Drupal
   *   Core entities like `node`, `taxonomy_term`, etc.
   * @param array $values
   *   A list of values to set in the created entity.
   * @param array $translations
   *   A list of translations to add to the created entity.
   * @param array $options
   *   A list of options to entity stub creation:
   *   - mockMethods: list of methods to make mockable.
   *   - addMethods: list of additional methods.
   *   - skipEntityConstructor: a flag to skip calling the entity constructor.
   *   - fields: a list of custom field options by field name.
   *    Applies only on the first initialization of this field.
   *    Field options supportable formats:
   *    - A string, indicating field type, like 'integer', 'string',
   *      'entity_reference', only core field types are supported.
   *    - An array with field type and settings, like this:
   *      [
   *       '#type' => 'entity_reference',
   *       '#settings' => ['target_type' => 'node']
   *      ].
   *    - A field definition object, that will be applied to the field.
   * @param array $storageOptions
   *   A list of options to pass to the storage initialization. Acts only once
   *   if the storage is not initialized yet.
   *   - skipPrePostSave: a flag to use direct save on the storage without
   *    calling preSave and postSave functions. Can be useful if that functions
   *    have dependencies which hard to mock.
   *   - constructorArguments: additional arguments to the constructor.
   *
   * @return \Drupal\test_helpers\StubFactory\EntityStubInterface|\Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The stub object for the entity.
   */
  public static function create(
    string $entityTypeNameOrClass,
    array $values = NULL,
    array $translations = NULL,
    array $options = NULL,
    array $storageOptions = NULL
  ) {
    $values ??= [];
    $options ??= [];

    TestHelpers::requireCoreFeaturesMap();
    $entityTypeClass = ltrim(TEST_HELPERS_DRUPAL_CORE_STORAGE_MAP[$entityTypeNameOrClass] ?? $entityTypeNameOrClass, '\\');

    if (is_array($options['methods'] ?? NULL)) {
      @trigger_error('The storage option "methods" is deprecated in test_helpers:1.0.0-beta9 and is removed from test_helpers:1.0.0-rc1. Use "mockMethods" instead. See https://www.drupal.org/project/test_helpers/issues/3347857', E_USER_DEPRECATED);
      $options['mockMethods'] = array_unique(array_merge($options['mockMethods'] ?? [], $options['methods']));
    }
    // Creating a new entity storage stub instance, if not exists.
    /**
     * @var \Drupal\test_helpers\Stub\EntityTypeManagerStub $entityTypeManager
     */
    $entityTypeManager = TestHelpers::service('entity_type.manager');
    /**
     * @var \Drupal\test_helpers\Stub\EntityFieldManagerStub $entityFieldManager
     */
    $entityTypeBundleInfo = TestHelpers::service('entity_type.bundle.info');
    /**
     * @var \Drupal\Core\Entity\EntityStorageInterface $storage
     */
    $storage = $entityTypeManager->stubGetOrCreateStorage($entityTypeClass, NULL, FALSE, $storageOptions);
    $entityTypeDefinition = $storage->getEntityType();
    $entityTypeId = $storage->getEntityTypeId();
    $bundleKey = $entityTypeDefinition->getKey('bundle');

    if ($bundleKey && ($values[$bundleKey] ?? NULL)) {
      $bundle = self::getFieldPlainValue($values[$bundleKey]);
      $bundleEntityType = $entityTypeDefinition->getBundleEntityType();
      $bundleStorage = $entityTypeManager->getStorage($bundleEntityType);
      if (!$bundleEntity = $bundleStorage->load($bundle)) {
        $idKey = $bundleStorage->getEntityType()->getKey('id');
        $labelKey = $bundleStorage->getEntityType()->getKey('label');
        $bundleEntity = $bundleStorage->create(
          [
            $idKey => $values[$bundleKey],
            $labelKey => $values[$bundleKey],
          ]
        );
        $bundleEntity->save();
      }
      $entityTypeBundleInfo->stubSetBundleInfo($entityTypeId, $bundle, $bundleEntity);
    }
    else {
      $bundle = $entityTypeId;
      $entityTypeBundleInfo->stubSetBundleInfo($entityTypeId, $bundle);
    }

    $methodsToMock = $options['mockMethods'] ?? [];
    if ($bundleKey) {
      $methodsToMock[] = 'bundleFieldDefinitions';
    }

    // @todo Remove this crunch.
    // $entityClass instanceOf ContentEntityBase doesn't work.
    if (in_array(ContentEntityBase::class, class_parents($entityTypeClass))) {
      $methodsToMock[] = 'updateOriginalValues';
    }
    $valuesForConstructor = [];
    foreach ($values as $key => $value) {
      if (!is_object($value)) {
        $valuesForConstructor[$key] = $value;
      }
    }
    $addMethods = [
      'stubInitValues',
      'stubSetFieldObject',
      ...($options['addMethods'] ?? []),
    ];
    /**
     * @var \Drupal\test_helpers\StubFactory\EntityStubInterface&\Drupal\Core\Entity\EntityInterface&\PHPUnit\Framework\MockObject\MockObject $entity
     */
    if ($options['skipEntityConstructor'] ?? NULL) {
      $entity = TestHelpers::createPartialMock(
        $entityTypeClass,
        [...$methodsToMock, ...$addMethods]
      );
    }
    else {
      $entity = TestHelpers::createPartialMockWithConstructor(
        $entityTypeClass,
        $methodsToMock,
        [
          $valuesForConstructor,
          $entityTypeId,
          $bundle,
          // Translations will be applied later, to support overrides of the
          // field definition settings.
          NULL,
        ],
        $addMethods
      );
    }
    // Adding empty values for obligatory fields, if not passed.
    foreach ($entityTypeDefinition->get('entity_keys') as $property) {
      if (!empty($property) && !isset($values[$property])) {
        $values[$property] = NULL;
      }
    }

    if ($bundleKey) {
      // Filling values to the entity array.
      TestHelpers::setMockedClassMethod(
        $entity,
        'bundleFieldDefinitions',
        function (EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
          return TestHelpers::service('entity_field.manager')->stubGetFieldDefinitons($entity_type, $bundle);
        }
      );
    }

    TestHelpers::setMockedClassMethod(
      $entity,
      'stubInitValues',
      function (array $values) use ($options, $entityTypeId, $bundle, $entityTypeDefinition) {
        if ($options['skipEntityConstructor'] ?? NULL) {
          // If we skipped the original constructor, we must define some
          // crucial things manually.
          /**
           * @var \Drupal\test_helpers\StubFactory\EntityStubInterface|\Drupal\Core\Entity\EntityInterface $this
           */
          $this->entityTypeId = $entityTypeId;
          $this->entityKeys['bundle'] = $bundle ? $bundle : $this->entityTypeId;
          foreach ($this->getEntityType()->getKeys() as $key => $field) {
            if (isset($values[$field])) {
              $this->entityKeys[$key] = $values[$field];
            }
          }
          $this->langcodeKey = $this->getEntityType()->getKey('langcode');
          $this->defaultLangcodeKey = $this->getEntityType()->getKey('default_langcode');
          $this->revisionTranslationAffectedKey = $this->getEntityType()->getKey('revision_translation_affected');

          if ($entityTypeDefinition->entityClassImplements(FieldableEntityInterface::class)) {
            $this->fieldDefinitions = TestHelpers::service('entity_field.manager')->getFieldDefinitions($entityTypeId, $bundle);
          }

          // Filling common values.
          $this->translations[LanguageInterface::LANGCODE_DEFAULT] = [
            'status' => TRUE,
            'entity' => $this,
          ];
          if ($this->defaultLangcodeKey) {
            $values[$this->defaultLangcodeKey] = $values[$this->defaultLangcodeKey] ?? 1;
          }

        }
        // Filling values to the entity array.
        foreach ($values as $name => $value) {
          if (isset($options['definitions'][$name])) {
            // @todo Deprecate this.
            $options['fields'][$name] = $options['definitions'][$name];
          }

          $newDefinition = NULL;
          $fieldType = NULL;
          $fieldTypeSettings = NULL;
          if ($fieldTypeOptions = $options['fields'][$name] ?? NULL) {
            if (is_object($fieldTypeOptions)) {
              $newDefinition = $fieldTypeOptions;
            }
            if (is_string($fieldTypeOptions)) {
              // Parsing value as a field type scalar value.
              $fieldType = $fieldTypeOptions;
              if ($fieldType == 'entity_reference') {
                throw new \Exception("For entity_reference field type you should also pass the settings like this ['#type' => 'entity_reference', '#settings' => ['target_type' => 'user'].");
              }
            }
            elseif (is_array($fieldTypeOptions)) {
              if (isset($fieldTypeOptions['#type'])) {
                // Parsing value as a field type definition.
                $fieldType = $fieldTypeOptions['#type'] ?? NULL;
                $fieldTypeSettings = $fieldTypeOptions['#settings'] ?? NULL;
              }
              else {
                // Interpeting array as field settings values.
                $fieldType = NULL;
                $fieldTypeSettings = $fieldTypeOptions;
              }
            }
            if ($fieldType) {
              if (!$itemDefinitionArray = TestHelpers::service('typed_data_manager')->getDefinition('field_item:' . $fieldType)) {
                throw new \Exception("A definition for a field type '$fieldType', passed in 'fields' options for field '$name', is not found.");
              }
              $newDefinition = FieldStorageDefinition::create($itemDefinitionArray['id']);
            }
          }

          if (!$newDefinition && !isset($this->fieldDefinitions[$name])) {
            // If we have no exact field type and no defined one, creating
            // a new definition.
            $newDefinition = FieldItemListStubFactory::createFieldItemDefinitionStub(ItemStubItem::class);
          }
          if ($newDefinition) {
            // We have no overrides, so checking the created definition or
            // create an item stub.
            $newDefinition->setName($name);
            $this->fieldDefinitions[$name] = $newDefinition;
            TestHelpers::service('entity_field.manager')->stubAddFieldDefiniton($entityTypeId, $bundle, $name, $newDefinition);
          }

          $definition = $this->fieldDefinitions[$name];
          if ($fieldTypeSettings) {
            $definition->setSettings($fieldTypeSettings);

            // @todo Replace to correct creation of definition with settings.
            if (isset($fieldTypeSettings['translatable'])) {
              $definition->setTranslatable($fieldTypeSettings['translatable']);
            }
          }

          $definition->setTargetBundle($bundle);

          if ($definition->getType() == 'entity_reference') {
            // Initializing storages for known references.
            switch ($definition->getSettings()['target_type']) {
              // @todo Move it to separate function that knows all core types.
              case 'user':
                TestHelpers::getEntityStorage(User::class);
                break;
            }
          }
          $field = FieldItemListStubFactory::create($name, $value, $definition, $this->typedData);
          if ($entityTypeDefinition->getGroup() == 'configuration') {
            $this->$name = $value;
          }
          else {
            if (is_object($value)) {
              $this->fields[$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
            }
            else {
              $this->fields[$name][LanguageInterface::LANGCODE_DEFAULT] = $field;
            }
          }
        }
      }
    );
    $entity->stubInitValues($values);
    // Applying tranlsations manually after all our initializations applied.
    if ($translations) {
      foreach ($translations as $langcode => $translation) {
        $entity->addTranslation($langcode, $translation);
      }
    }
    $entity->enforceIsNew();

    TestHelpers::setMockedClassMethod(
      $entity, 'stubSetFieldObject', function ($fieldName, $fieldObject, $langCode = NULL) {
        /**
         * @var \Drupal\test_helpers\StubFactory\EntityStubInterface|\Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject $this
         */
        $this->fieldDefinitions[$fieldName] = $fieldObject;
        $langCode ??= $this->activeLangCode;
        $this->fields[$fieldName][$langCode] = $fieldObject;
      }
    );

    if (array_search('updateOriginalValues', $methodsToMock)) {
      TestHelpers::setMockedClassMethod(
        $entity, 'updateOriginalValues', function (): void {
          /**
           * @var \Drupal\test_helpers\StubFactory\EntityStubInterface|\Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject $this
           */
          if (!$this->fields) {
            // Phpcs shows an error here: Function return type is not void, but
            // function is returning void here.
            // Suppressing it.
            // @codingStandardsIgnoreStart
            return;
            // @codingStandardsIgnoreEnd
          }
          foreach ($this->getFieldDefinitions() as $name => $definition) {
            if (!$definition->isComputed() && !empty($this->fields[$name])) {
              foreach ($this->fields[$name] as $langcode => $item) {
                $item->filterEmptyItems();
                // @todo Remove these crunches and use original function.
                // Crunches start.
                if (isset($this->values[$name]) && !is_array($this->values[$name])) {
                  $this->values[$name] = [];
                }
                // Crunches end.
                $this->values[$name][$langcode] = $item->getValue();
              }
            }
          }
        }
      );
    }
    return $entity;
  }

  /**
   * Gets the plain value of the field.
   *
   * Looking though array of deltas, array of values.
   *
   * @param mixed $value
   *   The complex field value.
   *
   * @return mixed
   *   The plain field value.
   */
  private static function getFieldPlainValue($value) {
    if (!is_array($value)) {
      return $value;
    }
    if (isset($value[0])) {
      $value = $value[0];
    }
    if (is_array($value)) {
      $value = current($value);
    }
    return $value;
  }

}
