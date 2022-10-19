<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * The Entity Storage Stub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
interface EntityStubInterface extends EntityInterface, ContentEntityInterface, MockObject {

  /**
   * Initializes values for entity from array.
   */
  public function stubInitValues(array $values): void;

}
