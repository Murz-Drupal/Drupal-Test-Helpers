<?php

namespace Drupal\test_helpers;

use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * The EntityStubFactory class.
 */
class EntityStubFactory {

  /**
   * Constructs a new EntityStubFactory.
   */
  public function __construct() {
    $this->unitTestCaseApi = UnitTestCaseApi::getInstance();
    if (!\Drupal::hasService('entity_type.manager')) {
      UnitTestHelpers::addToContainer('entity_type.manager', (new EntityTypeManagerStubFactory())->create());
    }
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    // $this->fieldTypeManagerStub = new FieldTypeManagerStub();
    $this->fieldItemListStubFactory = new FieldItemListStubFactory();
    $this->typedDataManagerStub = (new TypedDataManagerStubFactory())->createInstance();
    UnitTestHelpers::addToContainer('typed_data_manager', $this->typedDataManagerStub);
    UnitTestHelpers::addToContainer('uuid', new PhpUuid());
    $languageDefault = new LanguageDefault(['id' => 'en', 'name' => 'English']);
    UnitTestHelpers::addToContainer('language_manager', new LanguageManager($languageDefault));
  }

  /**
   * Creates an entity stub with field values.
   *
   * @param string $entityClass
   *   A class path to use when creating the entity.
   * @param array $values
   *   The array of values to set in the created entity.
   * @param array $options
   *   The array of options:
   *   - methods: the list of additional methods to allow mocking of them.
   *   - definitions: the list of custom field definitions for needed fields.
   *     If not passed - the default one (`StringItem`) will be used.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mocked entity object.
   */
  public function create(string $entityClass, array $values = [], array $options = []) {
    // Creating a new entity storage stub instance, if not exists.
    $storage = $this->entityTypeManager->stubGetOrCreateStorage($entityClass);
    $entityTypeDefinition = $storage->getEntityType();
    $entityTypeId = $storage->getEntityTypeId();
    $bundle = $values[$entityTypeDefinition->getKey('bundle')] ?? $entityTypeId;

    // Creating a stub of the entity.
    // @todo Try to init with a real constructor.
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
    $entity = $this->unitTestCaseApi->createPartialMock($entityClass, [
      // 'getEntityTypeId',
      // 'getFieldDefinitions',
      'save',
      'delete',
      'stubInitValues',
      ...($options['methods'] ?? []),
    ]);

    // Adding empty values for obligatory fields, if not passed.
    foreach ($entityTypeDefinition->get('entity_keys') as $property) {
      if (!isset($values[$property])) {
        $values[$property] = NULL;
      }
    }

    // Filling values to the entity array.
    $fieldItemListStubFactory = $this->fieldItemListStubFactory;
    UnitTestHelpers::bindClosureToClassMethod(
      function (array $values) use ($fieldItemListStubFactory, $options, $entityTypeId, $bundle) {
        // Pre-filling entity keys.
        /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $this */
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

        // Filling common values.
        $this->translations[LanguageInterface::LANGCODE_DEFAULT] = [
          'status' => TRUE,
          'entity' => $this,
        ];

        // Filling values to the entity array.
        foreach ($values as $name => $value) {
          if (isset($options['definitions'][$name])) {
            $definition = $options['definitions'][$name];
          }
          // @todo Convert entity to TypedDataInterface and pass to the
          // item list initialization as a third argument $parent.
          // $parent = EntityAdapter::createFromEntity($this);
          $parent = NULL;
          $field = $fieldItemListStubFactory->create($name, $value, $definition ?? NULL, $parent);
          $this->fieldDefinitions[$name] = $field->getFieldDefinition();
          $this->fields[$name][LanguageInterface::LANGCODE_DEFAULT] = $field;
        }

        /**
         * @todo Register field definitions to the EntityFieldManager via
         * \Drupal::service('entity_field.manager')->stubSetBaseFieldDefinitons($this->fieldDefinitions);
         */
      },
      $entity,
      'stubInitValues'
    );
    $entity->stubInitValues($values);

    UnitTestHelpers::bindClosureToClassMethod(
      function () use ($storage) {
        $idProperty = $this->getEntityType()->getKey('id') ?? NULL;
        if ($idProperty && empty($this->$idProperty->value)) {
          $this->$idProperty = $storage->stubGetNewEntityId();
        }

        $uuidProperty = $this->getEntityType()->getKey('uuid') ?? NULL;
        if ($uuidProperty && empty($this->$uuidProperty->value)) {
          $this->$uuidProperty = \Drupal::service('uuid')->generate();
        }

        $storage->stubAddEntity($this);

        return $this;
      },
      $entity,
      'save'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function () use ($storage) {
        $storage->stubDeleteEntity($this);
      },
      $entity,
      'delete'
    );

    return $entity;
  }

  /**
   * Returns the FieldTypeManagerStub.
   */
  public function getFieldTypeManagerStub() {
    return $this->fieldTypeManagerStub;
  }

  /**
   * Returns the FieldItemListStubFactory.
   */
  public function getFieldItemListStubFactory() {
    return $this->fieldItemListStubFactory;
  }

  /**
   * Returns the TypedDataManagerStub.
   */
  public function getTypedDataManagerStub() {
    return $this->typedDataManagerStub;
  }

  /**
   * Generates a new entity id, using auto increment like method.
   */
  public function generateNewEntityId(string $entityType): string {
    // @todo Make detection of id field type, and calculate only for integers.
    $id = max(array_keys($this->entitiesStorageById[$entityType] ?? [0])) + 1;
    // The `id` value for even integer autoincrement is stored as string in
    // Drupal, so we should follow this behaviour too.
    return (string) $id;
  }

}
