<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepository;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\test_helpers\StubFactory\EntityStorageStubFactory;
use Drupal\test_helpers\UnitTestCaseWrapper;
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
    $namespaces ??= new \ArrayObject([]);
    $module_handler ??= TestHelpers::service('module_handler');
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
      'entity_type.manager' => $this,
      'entity_type.repository' => new EntityTypeRepository($this),
      'entity_type.bundle.info' => NULL,
      'entity.memory_cache' => NULL,
      'entity_field.manager' => NULL,
      'language_manager' => NULL,
      'uuid' => NULL,
      'entity.query.sql' => new EntityQueryServiceStub(),
      'plugin.manager.field.field_type' => new FieldTypeManagerStub(),
    ]);
    TestHelpers::service('entity_field.manager', new EntityFieldManagerStub());

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityRepository = TestHelpers::service('entity.repository', TestHelpers::createMock(EntityRepositoryInterface::class));
    $entityRepository
      ->method('loadEntityByUuid')
      ->willReturnCallback(function ($entityTypeId, $uuid) {
        $entityTypeStorage = \Drupal::service('entity_type.manager')->getStorage($entityTypeId);
        $uuidProperty = $entityTypeStorage->getEntityType()->getKey('uuid');
        return current($entityTypeStorage->loadByProperties([$uuidProperty => $uuid]) ?? []);
      });

    $entityRepository
      ->method('getTranslationFromContext')
      ->will(UnitTestCaseWrapper::getInstance()->returnArgument(0));

    // @todo Make a proper call of parent::__construct() instead of manual
    // assinging all here.
    $this->entityLastInstalledSchemaRepository = TestHelpers::createMock(EntityLastInstalledSchemaRepository::class);
    $this->moduleHandler = TestHelpers::service('module_handler');
    $this->stringTranslation = TestHelpers::service('string_translation');
  }

  /**
   * {@inheritDoc}
   */
  public function findDefinitions() {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function stubSetDefinition(string $pluginId, object $definition = NULL, $forceOverride = FALSE) {
    if ($forceOverride || !isset($this->definitions[$pluginId])) {
      $this->definitions[$pluginId] = $definition;
    }
    return $this->definitions[$pluginId];
  }

  /**
   * {@inheritDoc}
   */
  public function stubGetOrCreateHandler(string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE) {
    if ($forceOverride || !isset($this->handlers[$handlerType][$entityTypeId])) {
      $this->handlers[$handlerType][$entityTypeId] = $handler;
    }
    return $this->handlers[$handlerType][$entityTypeId];
  }

  /**
   * {@inheritDoc}
   */
  public function stubGetOrCreateStorage(string $entityClass, $storageInstanceOrAnnotation = NULL, $forceOverride = FALSE, array $methods = [], array $addMethods = []) {
    $options = [
      'methods' => $methods,
      'addMethods' => $addMethods,
    ];
    if (!$forceOverride && isset($this->stubEntityStoragesByClass[$entityClass])) {
      return $this->stubEntityStoragesByClass[$entityClass];
    }
    elseif (is_object($storageInstanceOrAnnotation)) {
      $storage = $storageInstanceOrAnnotation;
    }
    // In this case the annotation is passed, so we should manually initiate
    // the storage instance.
    elseif (is_string($storageInstanceOrAnnotation)) {
      $storage = EntityStorageStubFactory::create($entityClass, $storageInstanceOrAnnotation, $options);
    }
    else {
      $storage = EntityStorageStubFactory::create($entityClass, NULL, $options);
    }
    $entityTypeId = $storage->getEntityTypeId();
    $this->stubEntityStoragesByClass[$entityClass] = $storage;
    $this->handlers['storage'][$entityTypeId] = $storage;
    $this->definitions[$entityTypeId] = $storage->getEntityType();

    if ($bundleEntityType = $this->definitions[$entityTypeId]->getBundleEntityType()) {
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
   * {@inheritDoc}
   */
  public function stubReset(): void {
    $this->handlers = [];
    $this->definitions = [];
  }

}
