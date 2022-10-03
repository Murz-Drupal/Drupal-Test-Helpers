<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * The Entity Storage Stub class factory.
 *
 * Stub factory for class Drupal\Core\Entity\Sql\SqlContentEntityStorage.
 */
class EntityStorageStubFactory {

  /**
   * Constructs a new EntityStorageStubFactory.
   */
  public function __construct() {
    $this->unitTestHelpers = UnitTestHelpers::getInstance();
  }

  /**
   * Creates an entity type stub and defines a static storage for it.
   */
  public function createInstance(string $entityClass) {
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit\Framework\MockObject\MockObject $entityStorageStub */
    $entityStorageStub = $this->unitTestHelpers->createPartialMock(SqlContentEntityStorage::class, [
      'loadMultiple',
      'loadByProperties',
      'delete',
      'invokeHook',

      // Custom helper functions for the stub:
      // Attaches an entity type object to the stub.
      'setEntityType',

      // Generates a next entity id, emulating DB autoincrement behavior.
      'stubGetNewEntityId',

      // Adds or replaces an entity to the static storage.
      'stubAddEntity',

      // Deletes an entity from the static storage.
      'stubDeleteEntity',
    ]);

    $entityType = $this->initEntityDefinition($entityClass);

    // @todo Temporary workaround to reuse an already defined property, because
    // we've got an error "Indirect modification of overloaded property
    // Mock_SqlContentEntityStorage_6202ec22::$stubStorage has no effect" if
    // try to use a new own property for this.
    $propertyToStoreEntities = 'database';

    $entityStorageStub->stubEntityStorageById = [];

    UnitTestHelpers::bindClosureToClassMethod(
      function () {
      },
      $entityStorageStub,
      'invokeHook'
     );

    UnitTestHelpers::bindClosureToClassMethod(
      function (EntityTypeInterface $entityType) {
        $this->entityType = $entityType;
        $this->entityTypeId = $entityType->id();
        return $this->entityType;
      },
      $entityStorageStub,
      'setEntityType'
     );

    $entityStorageStub->setEntityType($entityType);

    UnitTestHelpers::bindClosureToClassMethod(
      function (?array $ids = NULL) use ($propertyToStoreEntities) {
        if ($ids === NULL) {
          return $this->$propertyToStoreEntities;
        }
        $entities = [];
        foreach ($ids as $id) {
          if (isset($this->$propertyToStoreEntities[$id])) {
            $entities[$id] = $this->$propertyToStoreEntities[$id];
          }
        }
        return $entities;
      },
      $entityStorageStub,
      'loadMultiple'
     );

    UnitTestHelpers::bindClosureToClassMethod(
      function (array $values = []) use ($propertyToStoreEntities) {
        $entities = [];
        if (is_iterable($this->$propertyToStoreEntities)) {
          foreach ($this->$propertyToStoreEntities as $entity) {
            foreach ($values as $key => $value) {
              // Now getting only the `value` property to compare.
              // @todo Try to check the main property and get it.
              if (
                empty($entity->$key)
                || empty($entity->$key->value)
                || $entity->$key->value != $value
              ) {
                continue 2;
              }
            }
            $entities[] = $entity;
          }
        }
        return $entities;
      },
      $entityStorageStub,
      'loadByProperties'
     );

    UnitTestHelpers::bindClosureToClassMethod(
      function () use ($propertyToStoreEntities) {
        // @todo Make detection of id field type, and calculate only for integers.
        $id = max(array_keys($this->$propertyToStoreEntities ?? [0])) + 1;
        // The `id` value for even integer autoincrement is stored as string in
        // Drupal, so we should follow this behaviour too.
        return (string) $id;
      },
      $entityStorageStub,
      'stubGetNewEntityId'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function ($entity) use ($propertyToStoreEntities) {
        $this->$propertyToStoreEntities[$entity->id()] = $entity;
      },
      $entityStorageStub,
      'stubAddEntity'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function ($entity) use ($propertyToStoreEntities) {
        unset($this->$propertyToStoreEntities[$entity->id()]);
      },
      $entityStorageStub,
      'stubDeleteEntity'
    );
    $this->entitiesStorageById[$entityType->id()] = $entityStorageStub;
    return $entityStorageStub;
  }

  /**
   * Initializes an entity definition and adds to storage. Not working yet.
   */
  public function initEntityDefinition($entityClass) {
    $entityTypeDefinition = UnitTestHelpers::getPluginDefinition($entityClass, 'Entity', '\Drupal\Core\Entity\Annotation\ContentEntityType');
    $entityTypeId = $entityTypeDefinition->get('id');
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $entityTypeDefinition = $entityTypeManager->getDefinition($entityTypeId, FALSE);
    if (!$entityTypeDefinition) {
      $entityTypeDefinition = UnitTestHelpers::getPluginDefinition($entityClass, 'Entity');
      $entityTypeManager->stubAddDefinition($entityTypeId, $entityTypeDefinition);
    }
    return $entityTypeDefinition;
  }

}
