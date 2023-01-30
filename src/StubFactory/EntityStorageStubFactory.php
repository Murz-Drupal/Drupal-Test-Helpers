<?php

namespace Drupal\test_helpers\StubFactory;

use Drupal\Core\Entity\EntityInterface;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default SqlContentEntityStorage class.
 */
class EntityStorageStubFactory {

  /**
   * Disables the constructor to use only static methods.
   */
  private function __construct() {
  }

  /**
   * Creates a new Entity Storage Stub object.
   *
   * @param string $entityTypeClass
   *   The original class to use for stub.
   * @param mixed $annotation
   *   The annotation to use. If missing - tries ContentEntityType and
   *   ConfigEntityType.
   *   Examples:
   *   - \Drupal\Core\Entity\Annotation\ContentEntityType
   *   - \Drupal\Core\Entity\Annotation\ConfigEntityType
   *   or other annotations.
   * @param array $options
   *   The array of options:
   *   - constructorArguments: additional arguments to the constructor.
   *   - methods: list of methods to make mockable.
   *   - addMethods: list of additional methods.
   *
   * @throws \Exception
   *   When the annotation cannot be parsed.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked Entity Storage Stub.
   */
  public static function create(string $entityTypeClass, $annotation = NULL, array $options = []) {
    switch ($annotation) {
      case 'ContentEntityType':
      case 'ConfigEntityType':
        $annotation = '\Drupal\Core\Entity\Annotation\\' . $annotation;
    }

    if ($annotation) {
      $entityTypeDefinition = TestHelpers::getPluginDefinition($entityTypeClass, 'Entity', $annotation);
    }
    else {
      $annotation = '\Drupal\Core\Entity\Annotation\ContentEntityType';
      $entityTypeDefinition = TestHelpers::getPluginDefinition($entityTypeClass, 'Entity', $annotation);
      if ($entityTypeDefinition == NULL) {
        $annotation = '\Drupal\Core\Entity\Annotation\ConfigEntityType';
        $entityTypeDefinition = TestHelpers::getPluginDefinition($entityTypeClass, 'Entity', $annotation);
      }
    }

    if ($entityTypeDefinition == NULL) {
      throw new \Exception("Can't parse annotation for class \$entityTypeClass using annotation $annotation");
    }

    $entityTypeStorage = $entityTypeDefinition->getStorageClass();
    $staticStorage = &TestHelpers::service('test_helpers.static_storage')->get('test_helpers.entity_storage_stub.' . $entityTypeDefinition->id());
    if ($staticStorage === NULL) {
      $staticStorage = [];
    }

    TestHelpers::service('entity_type.manager')->stubSetDefinition($entityTypeDefinition->id(), $entityTypeDefinition);

    $constructArguments = NULL;

    if ($options['constructorArguments'] ?? NULL) {
      $constructArguments = $options['constructorArguments'];
    }
    switch ($annotation) {
      case '\Drupal\Core\Entity\Annotation\ContentEntityType':
        $constructArguments ??= [
          $entityTypeDefinition,
          TestHelpers::service('database'),
          TestHelpers::service('entity_field.manager'),
          TestHelpers::service('cache.entity'),
          TestHelpers::service('language_manager'),
          TestHelpers::service('entity.memory_cache'),
          TestHelpers::service('entity_type.bundle.info'),
          TestHelpers::service('entity_type.manager'),
        ];
        break;

      case '\Drupal\Core\Entity\Annotation\ConfigEntityType':
        // Does nothing for now.
        // @todo Maybe we need to pass some services.
        break;
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
      $entityStorage = TestHelpers::createPartialMockWithConstructor(
        $entityTypeStorage,
        $overridedMethods,
        $constructArguments,
        $addMethods,
      );
    }
    else {
      // Custom constructor.
      $entityStorage = TestHelpers::createPartialMock(
        $entityTypeStorage,
        [
          ...$overridedMethods,
          ...$addMethods,
          'stubInit',
        ],
      );
      TestHelpers::setMockedClassMethod($entityStorage, 'stubInit', function () use ($entityTypeDefinition) {
        $this->entityType = $entityTypeDefinition;
        $this->entityTypeId = $this->entityType->id();

        $this->baseEntityClass = $this->entityType->getClass();
        $this->entityTypeBundleInfo = TestHelpers::service('entity_type.bundle.info');

        $this->database = TestHelpers::service('database');
        $this->memoryCache = TestHelpers::service('cache.backend.memory')->get('entity_storage_stub.memory_cache.' . $this->entityTypeId);
        $this->cacheBackend = TestHelpers::service('cache.backend.memory')->get('entity_storage_stub.cache.' . $this->entityTypeId);

      }, $entityStorage, 'stubInit');

      $entityStorage->stubInit();
    }

    TestHelpers::setMockedClassMethod($entityStorage, 'save', function (EntityInterface $entity) use (&$staticStorage) {
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
          TestHelpers::setProtectedProperty($entity, $idProperty, $id);
        }
      }

      $uuidProperty = $this->entityType->getKey('uuid') ?? NULL;
      if ($uuidProperty && empty($entity->uuid())) {
        $uuid = TestHelpers::service('uuid')->generate();
        if (isset($entity->$uuidProperty)) {
          $entity->$uuidProperty = $uuid;
        }
        else {
          // For ConfigEntityType the uuid is protected.
          TestHelpers::setProtectedProperty($entity, $uuidProperty, $uuid);
        }
      }

      $staticStorage[$entity->id()] = $entity;

      return $return;
    });

    TestHelpers::setMockedClassMethod($entityStorage, 'delete', function (array $entities) use (&$staticStorage) {
      foreach ($entities as $entity) {
        $id = $entity->id();
        if (isset($staticStorage[$id])) {
          unset($staticStorage[$id]);
        }
      }
    });

    TestHelpers::setMockedClassMethod($entityStorage, 'loadMultiple', function (array $ids = NULL) use (&$staticStorage) {
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
    });

    TestHelpers::setMockedClassMethod($entityStorage, 'stubGetNewEntityId', function () use (&$staticStorage) {
      // @todo Make detection of id field type, and calculate only for integers.
      $id = (empty($staticStorage) ? 0 : max(array_keys($staticStorage))) + 1;
      // The `id` value for even integer autoincrement is stored as string in
      // Drupal, so we should follow this behaviour too.
      return (string) $id;
    });

    return $entityStorage;
  }

}
