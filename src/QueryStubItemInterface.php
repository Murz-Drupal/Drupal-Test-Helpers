<?php

namespace Drupal\test_helpers;

use Drupal\Core\Database\Query\Condition;

/**
 * The Entity Storage Stub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
interface QueryStubItemInterface {

  /**
   * Instantiates an entity query for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   * @param \Closure $executeFunction
   *   The function to use for `execute` call.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query for a specific configuration entity type.
   */
  public function stubCheckConditionsMatch(Condition $conditionsExpected, $onlyListed = FALSE): bool;

}
