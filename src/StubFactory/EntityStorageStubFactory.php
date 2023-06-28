<?php

namespace Drupal\test_helpers\StubFactory;

use Drupal\Core\Config\Entity\Query\QueryFactory;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default SqlContentEntityStorage class.
 */
class EntityStorageStubFactory {
  /**
   * A static storge for entity data per entity type.
   *
   * @var array
   */
  private static array $entityDataStorage = [];

  /**
   * Disables the constructor to use only static methods.
   */
  private function __construct() {
  }

  /**
   * Creates a new Entity Storage Stub object.
   *
   * @param string $entityClassOrName
   *   The original class to use for stub, or an entity type for types in.
   * @param string $annotation
   *   The annotation to use. If missing - tries ContentEntityType and
   *   ConfigEntityType.
   *   Examples:
   *   - \Drupal\Core\Entity\Annotation\ContentEntityType
   *   - \Drupal\Core\Entity\Annotation\ConfigEntityType
   *   or other annotations.
   * @param array|null $storageOptions
   *   The array of options:
   *   - constructorArguments: additional arguments to the constructor.
   *   - mockMethods: list of methods to make mockable.
   *   - addMethods: list of additional methods.
   *   - skipPrePostSave: a flag to use direct save on the storage without
   *     calling preSave and postSave functions. Can be useful if that functions
   *     have dependencies which hard to mock.
   *   - skipModuleFile: skips including of ".module" file.
   *
   * @throws \Exception
   *   When the annotation cannot be parsed.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked Entity Storage Stub.
   */
  public static function create(string $entityClassOrName, string $annotation = NULL, array $storageOptions = NULL) {
    $storageOptions ??= [];
    if (is_array($storageOptions['methods'] ?? NULL)) {
      @trigger_error('The storage option "methods" is deprecated in test_helpers:1.0.0-beta9 and is removed from test_helpers:1.0.0-rc1. Use "mockMethods" instead. See https://www.drupal.org/project/test_helpers/issues/3347857', E_USER_DEPRECATED);
      $storageOptions['mockMethods'] = array_unique(array_merge($storageOptions['mockMethods'] ?? [], $storageOptions['methods']));
    }
    TestHelpers::requireCoreFeaturesMap();
    $entityClass = ltrim(TEST_HELPERS_DRUPAL_CORE_STORAGE_MAP[$entityClassOrName] ?? $entityClassOrName, '\\');
    // $entityClass = $entityTypeNameOrClass;
    switch ($annotation) {
      case 'ContentEntityType':
      case 'ConfigEntityType':
        $annotation = '\Drupal\Core\Entity\Annotation\\' . $annotation;
    }

    if ($annotation) {
      $entityTypeDefinition = TestHelpers::getPluginDefinition($entityClass, 'Entity', $annotation);
    }
    else {
      // Starting with the Content Entity type at first.
      $annotation = '\Drupal\Core\Entity\Annotation\ContentEntityType';
      $entityTypeDefinition = TestHelpers::getPluginDefinition($entityClass, 'Entity', $annotation);
      if ($entityTypeDefinition == NULL) {
        // If it fails - use Config Entity type.
        $annotation = '\Drupal\Core\Entity\Annotation\ConfigEntityType';
        $entityTypeDefinition = TestHelpers::getPluginDefinition($entityClass, 'Entity', $annotation);
      }
    }

    if ($entityTypeDefinition == NULL) {
      throw new \Exception("Can't parse annotation for class $entityClass using annotation $annotation");
    }

    $entityTypeId = $entityTypeDefinition->id();

    // Some entity types depends on hook functions in the module file,
    // so trying to include this file.
    // @todo Add an option to disable this.
    if (!($storageOptions['skipModuleFile'] ?? NULL) && $entityFile = TestHelpers::getClassFile($entityClass)) {
      $moduleDirectory = dirname(dirname(dirname($entityFile)));
      $moduleName = basename($moduleDirectory);
      $moduleFile = "$moduleDirectory/$moduleName.module";
      file_exists($moduleFile) && include_once $moduleFile;
    }

    $entityTypeStorageClass = $entityTypeDefinition->getStorageClass();
    self::$entityDataStorage ??= [
      'value' => [],
      'maxId' => [],
      'maxRevisionId' => [],
    ];
    $entitiesStorage = &self::$entityDataStorage['value'][$entityTypeId];
    $entitiesStorage = [];
    $entitiesMaxIdStorage = &self::$entityDataStorage['maxId'][$entityTypeId];
    $entitiesMaxIdStorage = 0;
    $entitiesMaxRevisionIdStorage = &self::$entityDataStorage['maxRevisionId'][$entityTypeId];
    $entitiesMaxRevisionIdStorage = 0;
    TestHelpers::service('entity_field.manager')->stubClearFieldDefinitons($entityTypeId);
    TestHelpers::service('entity_type.manager')->stubSetDefinition($entityTypeId, $entityTypeDefinition);

    $constructArguments = NULL;

    if ($storageOptions['constructorArguments'] ?? NULL) {
      $constructArguments = $storageOptions['constructorArguments'];
    }
    $overridedMethods = [];
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
        $overridedMethods[] = 'loadMultiple';
        $overridedMethods[] = 'loadRevision';
        $overridedMethods[] = 'delete';
        $overridedMethods[] = ($storageOptions['skipPrePostSave'] ?? NULL) ? 'save' : 'doSaveFieldItems';

        break;

      case '\Drupal\Core\Entity\Annotation\ConfigEntityType':
        switch ($entityClass) {
          case "Drupal\Core\Field\Entity\BaseFieldOverride":
            break;

          default:
            if ($storageOptions['skipPrePostSave'] ?? NULL) {
              $overridedMethods[] = 'loadMultiple';
              $overridedMethods[] = 'loadRevision';
              $overridedMethods[] = 'delete';
              $overridedMethods[] = 'save';
            }
            TestHelpers::service('test_helpers.keyvalue.memory');
            TestHelpers::service('module_handler');

            TestHelpers::service(
              'entity.query.config',
              new QueryFactory(
                TestHelpers::service('config.factory'),
                new KeyValueFactory(TestHelpers::getContainer(), ['default' => 'test_helpers.keyvalue.memory']),
                TestHelpers::service('config.manager')
              )
            );

            $constructArguments ??= [
              $entityTypeDefinition,
              TestHelpers::service('config.factory'),
              TestHelpers::service('uuid'),
              TestHelpers::service('language_manager'),
              TestHelpers::service('entity.memory_cache'),
            ];
            break;
        }
        break;
    }

    $addMethods = array_unique(
      [
        ...($storageOptions['addMethods'] ?? []),
        'stubGetAllLatestRevision',
      ]
    );

    $mockMethods = array_unique(array_merge($overridedMethods, $storageOptions['mockMethods'] ?? []));

    // Removing requested mocked methods from mocking by the current class.
    $overridedMethods = array_diff(
      $overridedMethods,
      [...$storageOptions['mockMethods'] ?? [], ...$addMethods]
    );
    /** @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject $entityStorage */
    if ($constructArguments) {
      $entityStorage = TestHelpers::createPartialMockWithConstructor(
        $entityTypeStorageClass,
        $mockMethods,
        $constructArguments,
        $addMethods,
      );
      $entityStorage->setModuleHandler(TestHelpers::service('module_handler'));
    }
    else {
      // Custom constructor.
      $entityStorage = TestHelpers::createPartialMock(
        $entityTypeStorageClass,
        [
          ...$mockMethods,
          ...$addMethods,
          'stubInit',
        ],
      );

      TestHelpers::setMockedClassMethod(
        $entityStorage, 'stubInit', function () use ($entityTypeDefinition) {
          $this->entityType = $entityTypeDefinition;
          $this->entityTypeId = $this->entityType->id();

          $this->baseEntityClass = $this->entityType->getClass();
          $this->entityTypeBundleInfo = TestHelpers::service('entity_type.bundle.info');

          $this->database = TestHelpers::service('database');
          $this->memoryCache = TestHelpers::service('cache.backend.memory')->get('entity_storage_stub.memory_cache.' . $this->entityTypeId);
          $this->cacheBackend = TestHelpers::service('cache.backend.memory')->get('entity_storage_stub.cache.' . $this->entityTypeId);

        }, $entityStorage, 'stubInit'
      );

      $entityStorage->stubInit();
    }

    $saveFunction = function (EntityInterface $entity, array $names = []) use (&$entitiesStorage, &$entitiesMaxIdStorage, &$entitiesMaxRevisionIdStorage) {
      /**
       * @var \Drupal\test_helpers\Stub\EntityStubInterface $this
       */
      $idProperty = $this->entityType->getKey('id') ?? NULL;
      if ($idProperty) {
        // The `id` value for even integer autoincrement is stored as string in
        // Drupal, so we should follow this behaviour too.
        // @todo Make detection of id field type, and calculate only for integers.
        $id = (string) EntityStorageStubFactory::processAutoincrementId($entitiesMaxIdStorage, $entity->id());
        if (isset($entity->$idProperty)) {
          $entity->$idProperty = $id;
        }
        else {
          // For ConfigEntityType the uuid is protected.
          TestHelpers::setPrivateProperty($entity, $idProperty, $id);
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
          TestHelpers::setPrivateProperty($entity, $uuidProperty, $uuid);
        }
      }

      if (($this->entityType instanceof ContentEntityTypeInterface) && $this->entityType->isRevisionable()) {
        $setRevisionId = function ($entity, $revisionId) {
          $revisionProperty = $this->entityType->getKey('revision') ?? NULL;
          $entityKeys = TestHelpers::getPrivateProperty($entity, 'entityKeys');
          $entityKeys['revision'] = $revisionId;
          TestHelpers::setPrivateProperty($entity, 'entityKeys', $entityKeys);
          if (isset($entity->$revisionProperty)) {
            $entity->$revisionProperty = $revisionId;
          }
          else {
            // For ConfigEntityType the uuid is protected.
            TestHelpers::setPrivateProperty($entity, $revisionProperty, $revisionId);
          }
        };

        if ($entity->isNewRevision()) {
          $revisionId = EntityStorageStubFactory::processAutoincrementId($entitiesMaxRevisionIdStorage);
          $setRevisionId($entity, $revisionId);
        }
        else {
          $revisionId = $entity->getRevisionId();
        }
        if ($entity instanceof TranslatableInterface) {
          foreach ($entity->getTranslationLanguages() as $langcode => $language) {
            if ($entityInLanguage = $entity->getTranslation($langcode)) {
              $setRevisionId($entityInLanguage, $revisionId);
            }
          }
        }
      }

      // For content entities we should look all translations.
      if ($entity instanceof TranslatableInterface) {
        $entityData = [];
        $entityData['#translationData'] = TRUE;
        foreach ($entity->getTranslationLanguages() as $langcode => $language) {
          if (!$entityInLanguage = $entity->getTranslation($langcode)) {
            break;
          }
          if ($entityInLanguage->isDefaultTranslation()) {
            $entityData['#default'] = EntityStorageStubFactory::entityToValues($entityInLanguage);
          }
          else {
            $entityData['#translations'][$langcode] = EntityStorageStubFactory::entityToValues($entityInLanguage);
          }
        }
      }
      else {
        $entityData = EntityStorageStubFactory::entityToValues($entity);
      }

      if ($this->entityType instanceof ContentEntityTypeInterface) {
        $entitiesStorage['byRevisionId'][$entity->getRevisionId()] = $entityData;
        if ($entity->isLatestRevision()) {
          $entitiesStorage['byIdLatestRevision'][$entity->id()] = $entityData;
          if (
            $entity->isNew()
            || $entity->isDefaultRevision()
            || !isset($entitiesStorage['byId'][$entity->id()])) {
            $entitiesStorage['byId'][$entity->id()] = $entityData;
          }
        }
      }
      else {
        $entitiesStorage['byId'][$entity->id()] = $entityData;

      }
    };

    if (in_array('doSaveFieldItems', $overridedMethods)) {
      TestHelpers::setMockedClassMethod($entityStorage, 'doSaveFieldItems', $saveFunction);
    }
    elseif (in_array('save', $overridedMethods)) {
      TestHelpers::setMockedClassMethod($entityStorage, 'save', $saveFunction);
    }

    if (in_array('delete', $overridedMethods)) {
      TestHelpers::setMockedClassMethod(
        $entityStorage, 'delete', function (array $entities) use (&$entitiesStorage) {
          foreach ($entities as $entity) {
            $id = $entity->id();
            if (isset($entitiesStorage['byId'][$id])) {
              unset($entitiesStorage['byId'][$id]);
            }
          }
        }
      );
    }

    if (in_array('loadMultiple', $overridedMethods)) {
      TestHelpers::setMockedClassMethod(
        $entityStorage, 'loadMultiple', function (array $ids = NULL) use (&$entitiesStorage) {
          if ($ids === NULL) {
            $entitiesValues = $entitiesStorage['byId'] ?? [];
          }
          else {
            $entitiesValues = [];
            foreach ($ids as $id) {
              if (isset($entitiesStorage['byId'][$id])) {
                $entitiesValues[] = $entitiesStorage['byId'][$id];
              }
            }
          }

          $entities = [];
          foreach ($entitiesValues as $values) {
            $entity = EntityStorageStubFactory::valuesToEntity($this->entityType, $values);
            if (($this->entityType instanceof ContentEntityTypeInterface) && $this->entityType->isRevisionable()) {
              $entity->updateLoadedRevisionId();
            }
            $entities[$entity->id()] = $entity;
          }

          return $entities;
        }
      );
    }

    if (in_array('loadRevision', $overridedMethods)) {
      TestHelpers::setMockedClassMethod(
        $entityStorage, 'loadRevision', function ($id) use (&$entitiesStorage) {
          if (!$values = $entitiesStorage['byRevisionId'][$id] ?? NULL) {
            return NULL;
          }
          $entity = EntityStorageStubFactory::valuesToEntity($this->entityType, $values);
          if (($this->entityType instanceof ContentEntityTypeInterface) && $this->entityType->isRevisionable()) {
            $entity->updateLoadedRevisionId();
          }
          return $entity;
        }
      );
    }
    TestHelpers::setMockedClassMethod(
      $entityStorage, 'stubGetAllLatestRevision', function () use (&$entitiesStorage) {
        $entities = [];
        foreach ($entitiesStorage['byIdLatestRevision'] ?? [] as $values) {
          $entities[] = EntityStorageStubFactory::valuesToEntity($this->entityType, $values);
        }
        return $entities;
      }
    );

    // Crunches for known specific Core entity types.
    switch ($entityTypeId) {
      case 'block_content':
        // This service is required for preSave() to work well.
        TestHelpers::service('plugin.manager.block');
        break;

    }

    return $entityStorage;
  }

  /**
   * Converts entity to an array with values.
   *
   * @param mixed $entity
   *   The entity to use.
   *
   * @return array
   *   The array with values.
   */
  public static function entityToValues($entity) {
    $values = $entity->toArray();
    $keys = $entity->getEntityType()->getKeys();
    foreach ($keys as $key) {
      if (empty($values[$key])) {
        unset($values[$key]);
      }
    }
    return $values;
  }

  /**
   * Creates an entity from values array.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type to use.
   * @param array $values
   *   The values to use.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The created entity
   */
  public static function valuesToEntity(EntityTypeInterface $entityType, array $values = []): EntityInterface {
    if ($values['#translationData'] ?? NULL) {
      $entity = TestHelpers::createEntity($entityType->getClass(), $values['#default'] ?? []);
      $entity->enforceIsNew(FALSE);
      foreach ($values['#translations'] ?? [] as $langCode => $valuesInLang) {
        $entity->addTranslation($langCode, $valuesInLang);
      }
    }
    else {
      $entity = TestHelpers::createEntity($entityType->getClass(), $values);
    }
    if (($entityType instanceof ContentEntityTypeInterface) && $entityType->isRevisionable()) {
      $entity->updateLoadedRevisionId();
    }
    return $entity;
  }

  /**
   * Processes the autoincrement id to generate next values correctly.
   *
   * @param mixed $storage
   *   A static storage to use.
   * @param int|string $currentId
   *   The current id to use, or NULL to get the next autoincremented value.
   *
   * @return int|string
   *   The passed value or autoincremented, if passed is NULL.
   */
  public static function processAutoincrementId(&$storage, $currentId = NULL) {
    if ($currentId) {
      if ($currentId > $storage) {
        $storage = $currentId;
      }
      $id = $currentId;
    }
    else {
      $id = ++$storage;
    }
    return $id;
  }

}
