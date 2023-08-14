<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\ReplicaKillSwitch;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepository;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeGrantDatabaseStorage;
use Drupal\test_helpers\StubFactory\EntityStorageStubFactory;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * A stub of the Drupal's default EntityTypeManager class.
 */
class EntityTypeManagerStub extends EntityTypeManager implements EntityTypeManagerStubInterface {

  /**
   * Static storage for initialized entity storages.
   *
   * @var array
   */
  protected $stubEntityStoragesByClass;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces = NULL,
    ModuleHandlerInterface $module_handler = NULL,
    CacheBackendInterface $cache = NULL,
    TranslationInterface $string_translation = NULL,
    ClassResolverInterface $class_resolver = NULL,
    EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository = NULL
  ) {
    // Replacing missing arguments to mocks and stubs.
    $module_handler ??= TestHelpers::service('module_handler');
    // @todo Fill some right value!
    FileCacheFactory::setPrefix('test_helpers');
    $namespaces ??= new \ArrayObject([]);
    $cache ??= TestHelpers::service('cache.static');
    $string_translation ??= TestHelpers::service('string_translation');
    $class_resolver ??= TestHelpers::service('class_resolver');
    $entity_last_installed_schema_repository ??= TestHelpers::service('entity.last_installed_schema.repository');

    $this->setContainer(TestHelpers::getContainer());

    // Calling original costructor with mocked services.
    parent::__construct(
      $namespaces,
      $module_handler,
      $cache,
      $string_translation,
      $class_resolver,
      $entity_last_installed_schema_repository
    );

    TestHelpers::service('typed_data_manager', new TypedDataManagerStub());
    TestHelpers::setServices([
      'uuid' => NULL,
      'current_user' => NULL,
      'entity_type.manager' => $this,
      'entity_bundle.listener' => EntityBundleListenerStub::class,
      'entity.repository' => NULL,
      'entity_type.repository' => new EntityTypeRepository($this),
      'entity_type.bundle.info' => NULL,
      'entity.memory_cache' => NULL,
      'language_manager' => NULL,
      'entity.query.sql' => new EntityQueryServiceStub(),
      'plugin.manager.field.field_type' => new FieldTypeManagerStub(),
      'entity_field.manager' => NULL,
      'logger.factory' => NULL,
      // @todo Make a stub for it!
      'cache_tags.invalidator' => TestHelpers::createMock(CacheTagsInvalidatorInterface::class),
      // @todo Make a stub for it!
      'node.grant_storage' => TestHelpers::createMock(NodeGrantDatabaseStorage::class),
      // @todo Make a stub for it!
      'database.replica_kill_switch' => TestHelpers::createMock(ReplicaKillSwitch::class),
    ]);

    // @todo Make a proper call of parent::__construct() instead of manual
    // assinging all here.
    $this->entityLastInstalledSchemaRepository = TestHelpers::createMock(EntityLastInstalledSchemaRepository::class);
    $this->moduleHandler = TestHelpers::service('module_handler');
    $this->stringTranslation = TestHelpers::service('string_translation');
  }

  /**
   * {@inheritdoc}
   */
  public function findDefinitions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function stubSetDefinition(string $pluginId, object $definition = NULL, $forceOverride = FALSE) {
    if ($forceOverride || !isset($this->definitions[$pluginId])) {
      $this->definitions[$pluginId] = $definition;
    }
    return $this->definitions[$pluginId];
  }

  /**
   * {@inheritdoc}
   */
  public function stubGetOrCreateHandler(string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE) {
    if ($forceOverride || !isset($this->handlers[$handlerType][$entityTypeId])) {
      $this->handlers[$handlerType][$entityTypeId] = $handler;
    }
    return $this->handlers[$handlerType][$entityTypeId];
  }

  /**
   * {@inheritdoc}
   */
  public function stubGetOrCreateStorage(string $entityClassOrType, $storageInstance = NULL, ?bool $forceOverride = NULL, $storageOptions = NULL) {
    TestHelpers::requireCoreFeaturesMap();
    $entityClass = ltrim(TEST_HELPERS_DRUPAL_CORE_STORAGE_MAP[$entityClassOrType] ?? $entityClassOrType, '\\');
    if (!$forceOverride && isset($this->stubEntityStoragesByClass[$entityClass])) {
      return $this->stubEntityStoragesByClass[$entityClass];
    }
    elseif (is_object($storageInstance)) {
      $storage = $storageInstance;
      $storageDefinition = TestHelpers::getPluginDefinition($entityClass, 'Entity');
      $entityTypeId = $storageDefinition->id();
    }
    else {
      $storage = EntityStorageStubFactory::create($entityClass, NULL, $storageOptions);
      $entityTypeId = $storage->getEntityTypeId();
    }
    $this->stubEntityStoragesByClass[$entityClass] = $storage;
    $this->handlers['storage'][$entityTypeId] = $storage;
    $this->definitions[$entityTypeId] = $storage->getEntityType();

    if ($this->definitions[$entityTypeId] && $bundleEntityType = $this->definitions[$entityTypeId]->getBundleEntityType()) {
      // @todo Invent a better way to load the bundle entity type.
      $bundleEntityClassName = (new CamelCaseToSnakeCaseNameConverter(NULL, FALSE))->denormalize($bundleEntityType);
      $entityNamespace = substr($entityClass, 0, strrpos($entityClass, '\\'));
      $bundleEntityClass = $entityNamespace . '\\' . $bundleEntityClassName;
      if (class_exists($bundleEntityClass)) {
        self::stubGetOrCreateStorage($bundleEntityClass);
      }
    }

    return $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function stubReset(): void {
    $this->handlers = [];
    $this->definitions = [];
  }

}
