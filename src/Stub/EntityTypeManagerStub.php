<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManager;
use Drupal\test_helpers\UnitTestCaseWrapper;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * A stub of the Drupal's default EntityTypeManager class.
 */
class EntityTypeManagerStub extends EntityTypeManager implements EntityTypeManagerStubInterface {

  public function __construct() {
    $languageDefault = new LanguageDefault(['id' => 'en', 'name' => 'English']);
    UnitTestHelpers::addServices([
      'string_translation',
      'uuid',
      'language_manager' => new LanguageManager($languageDefault),
      'entity_field.manager' => new EntityFieldManagerStub(),
      'entity_type.bundle.info' => new EntityTypeBundleInfoStub(),
      'entity.query.sql' => new EntityQueryServiceStub(),
      'plugin.manager.field.field_type' => new FieldTypeManagerStub(),
      'typed_data_manager' => new TypedDataManagerStub(),
      'entity.query.sql' => new EntityQueryServiceStub(),
      'entity.query.sql' => new EntityQueryServiceStub(),
    ]);
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityRepository = UnitTestHelpers::addService('entity.repository', UnitTestHelpers::createMock(EntityRepositoryInterface::class));
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

    // UnitTestHelpers::addService('entity_type.manager', $this);
  }

  public function findDefinitions() {
    return [];
  }

  public function stubAddDefinition(string $pluginId, object $definition = NULL, $forceOverride = FALSE) {
    if ($forceOverride || !isset($this->definitions[$pluginId])) {
      $this->definitions[$pluginId] = $definition;
    }
    return $this->definitions[$pluginId];
  }

  public function stubGetOrCreateHandler(string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE) {
    if ($forceOverride || !isset($this->handlers[$handlerType][$entityTypeId])) {
      $this->handlers[$handlerType][$entityTypeId] = $handler;
    }
    return $this->handlers[$handlerType][$entityTypeId];
  }

  public function stubGetOrCreateStorage(string $entityClass, object $storageInstance = NULL, $forceOverride = FALSE) {
    if (!$forceOverride && isset($this->stubEntityStoragesByClass[$entityClass])) {
      return $this->stubEntityStoragesByClass[$entityClass];
    }
    elseif ($storageInstance) {
      $storage = $storageInstance;
    }
    else {
      $storage = new EntityStorageStub($entityClass);
    }
    $entityTypeId = $storage->getEntityTypeId();
    $this->stubEntityStoragesByClass[$entityClass] = $storage;
    $this->handlers['storage'][$entityTypeId] = $storage;
    $this->definitions[$entityTypeId] = $storage->getEntityType();
    return $storage;
  }

  public function stubCreateEntity(string $entityClass, array $values = [], array $options = []) {
    $storage = $this->stubGetOrCreateStorage($entityClass);
    return $storage->stubCreateEntity($entityClass, $values);
  }

  public function stubInit() {
    $this->container = \Drupal::getContainer();
    $this->entityLastInstalledSchemaRepository = UnitTestHelpers::createMock(EntityLastInstalledSchemaRepositoryInterface::class);
  }

  public function stubReset() {
    $this->handlers = [];
    $this->definitions = [];
  }

}
