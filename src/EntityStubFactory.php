<?php

namespace Drupal\test_helpers;

use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * The Entity Storage Stub class.
 */
class EntityStubFactory extends UnitTestCase {

  /**
   * Constructs a new EntityStubFactory.
   */
  public function __construct() {
    $this->fieldTypeManagerStub = new FieldTypeManagerStub();
    $this->fieldItemListStubFactory = new FieldItemListStubFactory($this->fieldTypeManagerStub);
    $this->entityTypeManager = (new EntityTypeManagerStubFactory())->create();
    $this->typedDataManagerStub = (new TypedDataManagerStubFactory())->createInstance();

    UnitTestHelpers::addToContainer('entity.repository', $this->createMock(EntityRepositoryInterface::class));
    UnitTestHelpers::addToContainer('entity_field.manager', $this->createMock(EntityFieldManagerInterface::class));
    UnitTestHelpers::addToContainer('entity_type.manager', $this->entityTypeManager);
    UnitTestHelpers::addToContainer('typed_data_manager', $this->typedDataManagerStub);
    UnitTestHelpers::addToContainer('uuid', new PhpUuid());

    // Reusing a string field type definition as default one.
    $stringItemDefinition = UnitTestHelpers::getPluginDefinition(StringItem::class, 'Field', '\Drupal\Core\Field\Annotation\FieldType');
    $this->fieldTypeManagerStub->addDefinition('string', $stringItemDefinition);

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityRepository = \Drupal::service('entity.repository');
    $entityRepository
      ->method('loadEntityByUuid')
      ->willReturnCallback(function ($entityTypeId, $uuid) {
        $entityTypeStorage = \Drupal::service('entity_type.manager')->getStorage($entityTypeId);
        $uuidProperty = $entityTypeStorage->getEntityType()->getKey('uuid');
        return current($entityTypeStorage->loadByProperties([$uuidProperty => $uuid]) ?? []);
      });

    $entityRepository
      ->method('getTranslationFromContext')
      ->will($this->returnArgument(0));

    $this->entityStorageStubFactory = new EntityStorageStubFactory();

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $entityFieldManager
      ->method('getFieldDefinitions')
      ->willReturnCallback(function ($entityTypeId, $bundle) {
        // @todo Make a proper return of field definitions.
        return [];
      });
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
    $storageNew = $this->entityStorageStubFactory->createInstance($entityClass);
    $entityTypeDefinition = $storageNew->getEntityType();
    $entityTypeId = $storageNew->getEntityTypeId();

    $storage = $this->entityTypeManager->stubGetOrCreateStorage($entityTypeId, $storageNew);
    $bundle = $values[$entityTypeDefinition->getKey('bundle')] ?? $entityTypeId;

    // Creating a stub of the entity.
    // @todo Try to init with a real constructor.
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
    $entity = $this->createPartialMock($entityClass, [
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
