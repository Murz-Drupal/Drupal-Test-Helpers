<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The EntityTypeManagerStubFactory class.
 */
interface EntityTypeManagerStubInterface extends EntityTypeManagerInterface {

  public function stubAddDefinition(string $pluginId, object $definition = NULL, $forceOverride = FALSE);

  public function stubGetOrCreateHandler(string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE);

  public function stubGetOrCreateStorage(string $entityClass, object $storage = NULL, $forceOverride = FALSE);

  public function stubInit();

  public function stubReset();

}
