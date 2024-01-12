<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The EntityTypeManagerStubFactory class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
interface EntityTypeManagerStubInterface extends EntityTypeManagerInterface {

  /**
   * Sets the definition to stub.
   *
   * @param string $pluginId
   *   The plugin id.
   * @param object|null $definition
   *   The definition.
   * @param mixed $forceOverride
   *   Forces override of already setted definition.
   *
   * @return mixed
   *   The definition.
   */
  public function stubSetDefinition(string $pluginId, object $definition = NULL, $forceOverride = FALSE);

  /**
   * Creates a new hanlder, or return exists one.
   *
   * @param string $handlerType
   *   The handler type.
   * @param string $entityTypeId
   *   The entity type id.
   * @param object|null $handler
   *   The hanlder object.
   * @param mixed $forceOverride
   *   Forces overriding of already existed one.
   *
   * @return mixed
   *   The handler.
   */
  public function stubGetOrCreateHandler(string $handlerType, string $entityTypeId, object $handler = NULL, $forceOverride = FALSE);

  /**
   * Creates a new entity type storage, or return exists one.
   *
   * @param string $entityClass
   *   The entity class.
   * @param object|null $storageInstanceOrAnnotation
   *   The storage object.
   * @param bool $forceOverride
   *   Forces overriding of already existed one.
   * @param array $storageOptions
   *   A list of options to pass to the storage initialization. Acts only once
   *   if the storage is not initialized yet.
   *   - skipPrePostSave: a flag to use direct save on the storage without
   *     calling preSave and postSave functions. Can be useful if that functions
   *     have dependencies which hard to mock.
   *   - fields: a list of custom field types to use, like 'integer',
   *     'string', 'entity_reference'. Only core field types are supported.
   *   - constructorArguments: additional arguments to the constructor.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked Entity Storage Stub.
   */
  public function stubGetOrCreateStorage(string $entityClass, $storageInstanceOrAnnotation = NULL, bool $forceOverride = NULL, array $storageOptions = NULL);

  /**
   * Resets the stub and clears all storages.
   */
  public function stubReset(): void;

}
