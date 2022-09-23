<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Tests\UnitTestCase;

/**
 * The Entity Storage Stub.
 */
class EntityTypeManagerStubFactory extends UnitTestCase {

  /**
   * Constructs a new FieldTypeManagerStub.
   */
  public function create() {
    /** @var \Drupal\Core\Entity\EntityTypeManager|\PHPUnit\Framework\MockObject\MockObject $entityTypeManagerStub */
    $entityTypeManagerStub = $this->createPartialMock(EntityTypeManager::class, [
      'findDefinitions',

      // Custom helper functions for the stub:
      // Adds a definition to the static storage.
      'stubAddDefinition',

      // Adds or creates a handler.
      'stubGetOrCreateHandler',

      // Adds or creates a storage.
      'stubGetOrCreateStorage',
    ]);

    UnitTestHelpers::bindClosureToClassMethod(
      function () {
        return [];
      },
      $entityTypeManagerStub,
      'findDefinitions'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function (string $pluginId, object $definition = NULL, $forceOverride = FALSE) {
        if ($forceOverride || !isset($this->definitions[$pluginId])) {
          $this->definitions[$pluginId] = $definition;
          // $this->handlers['storage'][$pluginId] =
        }
        return $this->definitions[$pluginId];
      },
      $entityTypeManagerStub,
      'stubAddDefinition'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function (string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE) {
        if ($forceOverride || !isset($this->handlers[$handlerType][$entityTypeId])) {
          $this->handlers[$handlerType][$entityTypeId] = $handler;
        }
        return $this->handlers[$handlerType][$entityTypeId];
      },
      $entityTypeManagerStub,
      'stubGetOrCreateHandler'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function (string $entityTypeId, object $storage = NULL, $forceOverride = FALSE) {
        $storage = $this->stubGetOrCreateHandler('storage', $entityTypeId, $storage);
        return $storage;
      },
      $entityTypeManagerStub,
      'stubGetOrCreateStorage'
    );
    return $entityTypeManagerStub;
  }

}
