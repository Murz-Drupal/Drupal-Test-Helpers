<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityTypeManager;

/**
 * The EntityTypeManagerStub class for internal usage only.
 *
 * This is an utility class for creating a partial mock with required interface.
 */
class EntityTypeManagerStub extends EntityTypeManager implements EntityTypeManagerStubInterface {

  public function stubAddDefinition(string $pluginId, object $definition = NULL, $forceOverride = FALSE) {
  }

  public function stubGetOrCreateHandler(string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE) {
  }

  public function stubGetOrCreateStorage(string $entityClass, object $storage = NULL, $forceOverride = FALSE) {
  }

  public function stubInit() {
  }

  public function stubReset() {
  }

}
