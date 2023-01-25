<?php

namespace Drupal\test_helpers\StubFactory;

use Drupal\Core\Entity\EntityInterface;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * A stub of the Drupal's default SqlContentEntityStorage class.
 */
class EntityStorageStubFactory {

  protected $stubEntities;

  protected $entityStubFactory;

  /**
   * A workaround to fix access to the private method of the parent class.
   */
  protected $baseEntityClass;

  /**
   * Disables the constructor to use only static methods.
   */
  private function __construct() {
  }

  public static function create(string $entityTypeClass, $annotation = '\Drupal\Core\Entity\Annotation\ContentEntityType', array $options = []) {

    $entityTypeDefinition = UnitTestHelpers::getPluginDefinition($entityTypeClass, 'Entity', $annotation);

    $entityTypeStorage = $entityTypeDefinition->getStorageClass();
    $staticStorage = &UnitTestHelpers::addService('test_helpers.static_storage')->get('test_helpers.entity_storage_stub.' . $entityTypeDefinition->id());

    UnitTestHelpers::addService('entity_type.manager')->stubSetDefinition($entityTypeDefinition->id(), $entityTypeDefinition);

    $constructArguments = NULL;

    if ($annotation == '\Drupal\Core\Entity\Annotation\ContentEntityType') {
      $constructArguments = [
        $entityTypeDefinition,
        UnitTestHelpers::addService('database'),
        UnitTestHelpers::addService('entity_field.manager'),
        UnitTestHelpers::addService('cache.entity'),
        UnitTestHelpers::addService('language_manager'),
        UnitTestHelpers::addService('entity.memory_cache'),
        UnitTestHelpers::addService('entity_type.bundle.info'),
        UnitTestHelpers::addService('entity_type.manager'),
      ];
    }
    elseif ($annotation == '\Drupal\Core\Entity\Annotation\ConfigEntityType') {
      // Does nothing as for now.
      // @todo Add list of required services.
    }

    $overridedMethods = [
      'loadMultiple',
      'save',
      'delete',
      ...($options['methods'] ?? []),
    ];

    $addMethods = [
      ...($options['addMethods'] ?? []),
      'stubGetNewEntityId',
    ];

    if ($constructArguments) {
      $entityStorage = UnitTestHelpers::createPartialMockWithConstructor(
        $entityTypeStorage,
        $overridedMethods,
        $constructArguments,
        $addMethods,
      );
    }
    else {
      // Custom constructor.
      $entityStorage = UnitTestHelpers::createPartialMock(
        $entityTypeStorage,
        [
          ...$overridedMethods,
          ...$addMethods,
          'stubInit',
        ],
      );
      UnitTestHelpers::bindClosureToClassMethod(function () use ($entityTypeDefinition, $entityTypeClass, $entityTypeStorage) {
        $this->entityType = $entityTypeDefinition;
        $this->entityTypeId = $this->entityType->id();

        $this->baseEntityClass = $this->entityType->getClass();
        // UnitTestHelpers::setProtectedProperty($this, 'baseEntityClass', $this->entityType->getClass());

        $this->entityTypeBundleInfo = UnitTestHelpers::addService('entity_type.bundle.info');

        $this->database = UnitTestHelpers::addService('database');
        $this->memoryCache = UnitTestHelpers::addService('cache.backend.memory')->get('entity_storage_stub.memory_cache.' . $this->entityTypeId);
        $this->cacheBackend = UnitTestHelpers::addService('cache.backend.memory')->get('entity_storage_stub.cache.' . $this->entityTypeId);

      }, $entityStorage, 'stubInit');

      $entityStorage->stubInit();
    }

    UnitTestHelpers::bindClosureToClassMethod(function (EntityInterface $entity) use (&$staticStorage) {
      require_once DRUPAL_ROOT . '/core/includes/common.inc';
      if ($entity->isNew()) {
        $return = SAVED_NEW;
      }
      else {
        $return = SAVED_UPDATED;
      }

      /** @var \Drupal\test_helpers\StubFactory\EntityStubInterface $this */
      $idProperty = $this->entityType->getKey('id') ?? NULL;
      if ($idProperty && empty($entity->id())) {
        $id = $this->stubGetNewEntityId();
        if (isset($entity->$idProperty)) {
          $entity->$idProperty = $id;
        }
        else {
          // For ConfigEntityType the uuid is protected.
          UnitTestHelpers::setProtectedProperty($entity, $idProperty, $id);
        }
      }

      $uuidProperty = $this->entityType->getKey('uuid') ?? NULL;
      if ($uuidProperty && empty($entity->uuid())) {
        $uuid = UnitTestHelpers::addService('uuid')->generate();
        if (isset($entity->$uuidProperty)) {
          $entity->$uuidProperty = $uuid;
        }
        else {
          // For ConfigEntityType the uuid is protected.
          UnitTestHelpers::setProtectedProperty($entity, $uuidProperty, $uuid);
        }
      }

      $staticStorage[$entity->id()] = $entity;

      return $return;
    }, $entityStorage, 'save');

    UnitTestHelpers::bindClosureToClassMethod(function (array $entities) use (&$staticStorage) {
      foreach ($entities as $entity) {
        $id = $entity->id();
        if (isset($staticStorage[$id])) {
          unset($staticStorage[$id]);
        }
      }
    }, $entityStorage, 'delete');

    UnitTestHelpers::bindClosureToClassMethod(function (array $ids = NULL) use (&$staticStorage) {
      if ($ids === NULL) {
        return $staticStorage;
      }
      $entities = [];
      foreach ($ids as $id) {
        if (isset($staticStorage[$id])) {
          $entities[$id] = $staticStorage[$id];
        }
      }
      return $entities;
    }, $entityStorage, 'loadMultiple');

    UnitTestHelpers::bindClosureToClassMethod(function () use (&$staticStorage) {
      // @todo Make detection of id field type, and calculate only for integers.
      $id = max(array_keys($staticStorage ?? [0])) + 1;
      // The `id` value for even integer autoincrement is stored as string in
      // Drupal, so we should follow this behaviour too.
      return (string) $id;
    }, $entityStorage, 'stubGetNewEntityId');

    return $entityStorage;
  }

}
