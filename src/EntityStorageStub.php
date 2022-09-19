<?php

namespace Drupal\test_helpers;

use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * The Entity Storage Stub class.
 */
class EntityStorageStub extends UnitTestCase {

  /**
   * Static storage for mocked entities by id.
   *
   * @var array
   */
  protected array $entitiesStorageById;

  /**
   * Static storage for mocked entities by uuid.
   *
   * @var array
   */
  protected array $entitiesStorageByUuid;

  /**
   * Static storage for Entity type storages stubs.
   *
   * @var array
   */
  protected array $entityStorageStubs;

  /**
   * Static storage for definitions.
   *
   * @var array
   */
  protected array $definitionStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct() {

    $this->fieldTypeManagerStub = new FieldTypeManagerStub();

    // Creating a stub to reuse by default for non defined field types.
    $this->fieldTypeManagerStub->addDefinition('type_stub');

    $this->fieldItemListStubFactory = new FieldItemListStubFactory($this->fieldTypeManagerStub);

    $this->entitiesStorageById = [];
    $this->entitiesStorageByUuid = [];

    UnitTestHelpers::addToContainer('entity.repository', $this->createMock(EntityRepositoryInterface::class));
    UnitTestHelpers::addToContainer('entity_type.manager', $this->createMock(EntityTypeManagerInterface::class));
    UnitTestHelpers::addToContainer('entity_field.manager', $this->createMock(EntityFieldManagerInterface::class));
    UnitTestHelpers::addToContainer('uuid', new PhpUuid());

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityRepository = \Drupal::service('entity.repository');
    $entityRepository
      ->method('loadEntityByUuid')
      ->willReturnCallback(function ($entityType, $uuid) {
        return $this->entitiesStorageByUuid[$entityType][$uuid] ?? NULL;
      });

    $entityRepository
      ->method('getTranslationFromContext')
      ->will($this->returnArgument(0));

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $entityTypeManager
      ->method('getStorage')
      ->willReturnCallback(function ($entityType) {
        return $this->entityStorageStubs[$entityType] ?? NULL;
      });
    $entityTypeManager
      ->method('getDefinition')
      ->willReturnCallback(function ($definitionId) {
        return $this->definitionStorage[$definitionId] ?? NULL;
      });

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
   * Creates an entity type stub and defines a static storage for it.
   */
  public function createEntityStorageStub(string $entityType) {
    if (!isset($this->entityStorageStubs[$entityType])) {
      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit\Framework\MockObject\MockObject $entityStorageStub */
      $entityStorageStub = $this->createPartialMock(
        SqlContentEntityStorage::class,
        [
          'loadMultiple',
          'loadByProperties',
        ]
      );
      $entityStorageStub
        ->method('loadMultiple')
        ->willReturnCallback(function (?array $ids = NULL) use ($entityType) {
          if ($ids === NULL) {
            return $this->entitiesStorageById[$entityType];
          }
          $entities = [];
          foreach ($ids as $id) {
            if (isset($this->entitiesStorageById[$entityType][$id])) {
              $entities[$id] = $this->entitiesStorageById[$entityType][$id];
            }
          }
          return $entities;
        });

      $entityStorageStub
        ->method('loadByProperties')
        ->willReturnCallback(function (array $values = []) use ($entityType) {
          $entities = [];
          foreach ($this->entitiesStorageById[$entityType] as $entity) {
            foreach ($values as $key => $value) {
              // Now getting only the `value` property to compare.
              // @todo Try to check the main property and get it.
              if ($entity->$key->value != $value) {
                continue;
              }
            }
            $entities[] = $entity;
          }
          return $entities;
        });

      $this->entityStorageStubs[$entityType] = $entityStorageStub;
    }
    return $this->entityStorageStubs[$entityType];
  }

  /**
   * Stores an entity object into the static storage.
   */
  public function storeEntity(EntityInterface $entity) {
    $entityTypeId = $entity->getEntityTypeId();
    $this->createEntityStorageStub($entityTypeId);
    $this->entitiesStorageById[$entityTypeId][$entity->id()] = $entity;
    if ($uuid = $entity->uuid()) {
      $this->entitiesStorageByUud[$entityTypeId][$uuid] = $entity;
    }
  }

  /**
   * Initializes an entity definition and adds to storage. Not working yet.
   */
  public function initEntityDefinition($entityClass) {
    $entityTypeDefinition = UnitTestHelpers::getPluginDefinition($entityClass, 'Entity');
    $entityTypeId = $entityTypeDefinition->get('id');

    if (!isset($this->definitionStorage[$entityTypeId])) {
      $entityTypeDefinition = UnitTestHelpers::getPluginDefinition($entityClass, 'Entity');
      $entityTypeId = $entityTypeDefinition->get('id');
      $this->definitionStorage[$entityTypeId] = $entityTypeDefinition;
    }
    return $this->definitionStorage[$entityTypeId];
  }

  /**
   * Creates an real entity with field values. Not working yet.
   *
   * @todo Make it work well.
   */
  public function createEntity(string $entityClass, array $values) {
    $entityTypeDefinition = $this->initEntityDefinition($entityClass);
    $entityTypeId = $entityTypeDefinition->get('id');
    $bundleProperty = $entityTypeDefinition->get('entity_keys')['bundle'];
    $entityBundle = $values[$bundleProperty] ?? $entityTypeDefinition->get('id');
    $entity = new $entityClass($values, $entityTypeId, $entityBundle ?? FALSE);
    return $entity;
  }

  /**
   * Creates an entity stub with field values.
   */
  public function createEntityStub(string $entityClass, array $values = []) {
    $entityTypeDefinition = $this->initEntityDefinition($entityClass);
    $entityTypeId = $entityTypeDefinition->get('id');
    $bundleProperty = $entityTypeDefinition->get('entity_keys')['bundle'];

    // Creating a new entity storage stub instance, if not exists.
    $this->entityStorageStubs[$entityTypeId] = $this->createEntityStorageStub($entityTypeId);

    // Creating a stub of the entity.
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
    $entity = $this->getMockBuilder($entityClass)
      ->disableOriginalConstructor()
      ->getMock();

    $this->bindClosureToClassMethod(
      function ($field) {
        return $this->values[$field] ?? NULL;
      },
      $entity,
      '__get'
    );

    $this->bindClosureToClassMethod(
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

    $this->bindClosureToClassMethod(
      function () use ($entityTypeId) {
        return $entityTypeId;
      },
      $entity,
      'getEntityTypeId'
    );

    $this->bindClosureToClassMethod(
      function () use ($bundleProperty) {
        return $this->$bundleProperty->value;
      },
      $entity,
      'bundle'
    );

    $idProperty = $entityTypeDefinition->get('entity_keys')['id'] ?? NULL;
    $uuidProperty = $entityTypeDefinition->get('entity_keys')['uuid'] ?? NULL;

    if ($idProperty) {
      $this->bindClosureToClassMethod(
        function () use ($idProperty) {
          return $this->values[$idProperty]->value;
        },
        $entity,
        'id'
      );
    }
    if ($uuidProperty) {
      $this->bindClosureToClassMethod(
        function () use ($uuidProperty) {
          return $this->values[$uuidProperty]->value;
        },
        $entity,
        'uuid'
      );
    }

    $entity
      ->method('save')
      ->willReturnCallback(function () use ($entity, $idProperty, $uuidProperty) {
        $entityTypeId = $entity->getEntityTypeId();
        if (!$entity->id()) {
          $entity->$idProperty = $this->generateNewEntityId($entityTypeId);
        }
        $this->entitiesStorageById[$entityTypeId][intval($entity->id())] = $entity;

        if ($uuidProperty && empty($entity->uuid())) {
          $uuid = \Drupal::service('uuid')->generate();
          $entity->$uuidProperty = $uuid;
        }
        if ($entity->uuid()) {
          $this->entitiesStorageByUuid[$entityTypeId][$entity->uuid()] = $entity;
        }
        return $entity;
      });

    return $entity;
  }

  /**
   * Binds a closure function to a mocked class method.
   */
  private function bindClosureToClassMethod(\Closure $closure, MockObject $class, string $method): void {
    $doClosure = $closure->bindTo($class, get_class($class));
    $class->method($method)->willReturnCallback($doClosure);
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
