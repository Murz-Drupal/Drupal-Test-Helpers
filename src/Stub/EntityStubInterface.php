<?php

namespace Drupal\test_helpers\Stub;

/**
 * The Entity Stub interface.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
interface EntityStubInterface {

  /**
   * Initializes values for entity from array.
   */
  public function stubInitValues(array $values): void;

  /**
   * Sets an object directluy to an entity field.
   *
   * @param string $fieldName
   *   A field name.
   * @param mixed $fieldObject
   *   An object to attach.
   * @param string|null $langCode
   *   A language code to use.
   */
  public function stubSetFieldObject(string $fieldName, $fieldObject, string $langCode = NULL): void;

}
