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
   * Performs a match for conditions with expected.
   */
  public function stubCheckConditionsMatch(Condition $conditionsExpected, $onlyListed = FALSE): bool;

}
