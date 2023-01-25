<?php

namespace Drupal\test_helpers\StubFactory;

/**
 * The Entity Storage Stub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
interface EntityStubInterface {

  /**
   * Initializes values for entity from array.
   */
  public function stubInitValues(array $values): void;

}
