<?php

namespace Drupal\test_helpers;

use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
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

    // Creating a stub to reuse by default for non defined field types.
    $this->fieldTypeManagerStub->addDefinition('type_stub');

    $this->fieldItemListStubFactory = new FieldItemListStubFactory($this->fieldTypeManagerStub);

    $this->entitiesStorageById = [];
    $this->entitiesStorageByUuid = [];

    $entityTypeManagerStubFactory = new EntityTypeManagerStubFactory();
    $this->entityTypeManager = $entityTypeManagerStubFactory->create();

    UnitTestHelpers::addToContainer('entity.repository', $this->createMock(EntityRepositoryInterface::class));
    UnitTestHelpers::addToContainer('entity_type.manager', $this->entityTypeManager);
    UnitTestHelpers::addToContainer('entity_field.manager', $this->createMock(EntityFieldManagerInterface::class));
    UnitTestHelpers::addToContainer('uuid', new PhpUuid());

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityRepository = \Drupal::service('entity.repository');
    $entityRepository
      ->method('loadEntityByUuid')
      ->willReturnCallback(function ($entityTypeId, $uuid) {
        $entityTypeStorage = \Drupal::service('entity_type.manager')->getStorage($entityTypeId);
        $uuidProperty = $entityTypeStorage->getEntityType()->getKeys()['uuid'];
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
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mocked entity object.
   */
  public function create(string $entityClass, array $values = [], array $options = []) {
    // Creating a new entity storage stub instance, if not exists.
    // @todo Move this to a separate function.
    $storageNew = $this->entityStorageStubFactory->createInstance($entityClass);
    $entityTypeDefinition = $storageNew->getEntityType();
    $bundleProperty = $entityTypeDefinition->getKeys()['bundle'];
    $entityTypeId = $storageNew->getEntityTypeId();

    $storage = $this->entityTypeManager->stubGetOrCreateStorage($entityTypeId, $storageNew);

    // Creating a stub of the entity.
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
    $entity = $this->createPartialMock($entityClass, [
      '__get',
      '__set',
      'id',
      'uuid',
      'bundle',
      'getEntityTypeId',
      'save',
      'delete',
      ...($options['methods'] ?? []),
    ]);

    UnitTestHelpers::bindClosureToClassMethod(
      function ($field) {
        return $this->values[$field] ?? NULL;
      },
      $entity,
      '__get'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function ($field, $value) {
        if (isset($this->values[$field])) {
          $this->values[$field]->setValue($value);
        }
        else {
          $this->values[$field] = $value;
        }
      },
      $entity,
      '__set'
    );

    // Adding empty values for obligatory fields, if not passed.
    foreach ($entityTypeDefinition->get('entity_keys') as $property) {
      if (!isset($values[$property])) {
        $values[$property] = NULL;
      }
    }

    // Filling values to the entity array.
    foreach ($values as $fieldName => $value) {
      $field = $this->fieldItemListStubFactory->create($fieldName, $value);
      $entity->$fieldName = $field;
    }

    UnitTestHelpers::bindClosureToClassMethod(
      function () use ($entityTypeId) {
        return $entityTypeId;
      },
      $entity,
      'getEntityTypeId'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function () use ($bundleProperty) {
        return $this->$bundleProperty->value;
      },
      $entity,
      'bundle'
    );

    $idProperty = $entityTypeDefinition->get('entity_keys')['id'] ?? NULL;
    $uuidProperty = $entityTypeDefinition->get('entity_keys')['uuid'] ?? NULL;

    if ($idProperty) {
      UnitTestHelpers::bindClosureToClassMethod(
        function () use ($idProperty) {
          return $this->values[$idProperty]->value;
        },
        $entity,
        'id'
      );
    }
    if ($uuidProperty) {
      UnitTestHelpers::bindClosureToClassMethod(
        function () use ($uuidProperty) {
          return $this->values[$uuidProperty]->value;
        },
        $entity,
        'uuid'
      );
    }

    UnitTestHelpers::bindClosureToClassMethod(
      function () use ($storage) {
        $idProperty = $this->getEntityType()->getKeys()['id'] ?? NULL;
        if ($idProperty && empty($this->$idProperty->value)) {
          $this->$idProperty = $storage->stubGetNewEntityId();
        }

        $uuidProperty = $this->getEntityType()->getKeys()['uuid'] ?? NULL;
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
   * Returns the Field Type Manager stub.
   */
  public function getFieldTypeManagerStub() {
    return $this->fieldTypeManagerStub;
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
