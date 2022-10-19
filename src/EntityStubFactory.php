<?php

namespace Drupal\test_helpers;

use Drupal\Core\Language\LanguageInterface;

/**
 * A factory for creating stubs of entities.
 */
class EntityStubFactory {

  private function __construct() {
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
   * @return \Drupal\test_helpers\EntityStubInterface
   *   A mocked entity object.
   */
  public static function create(string $entityClass, array $values = [], array $options = []) {
    // Creating a new entity storage stub instance, if not exists.
    $storage = \Drupal::service('entity_type.manager')->stubGetOrCreateStorage($entityClass);
    $entityTypeDefinition = $storage->getEntityType();
    $entityTypeId = $storage->getEntityTypeId();
    $bundle = $values[$entityTypeDefinition->getKey('bundle')] ?? $entityTypeId;
    \Drupal::service('entity_type.bundle.info')->stubAddBundleInfo($entityTypeId, $bundle);

    // Creating a stub of the entity.
    // @todo Try to init with a real constructor.
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
    $entity = UnitTestHelpers::createPartialMock($entityClass, [
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
    UnitTestHelpers::bindClosureToClassMethod(
      function (array $values) use ($options, $entityTypeId, $bundle) {
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
          $field = FieldItemListStubFactory::create($name, $value, $definition ?? NULL, $parent);
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
        $storage->stubDeleteEntityById($this->id());
      },
      $entity,
      'delete'
    );

    return $entity;
  }

}
