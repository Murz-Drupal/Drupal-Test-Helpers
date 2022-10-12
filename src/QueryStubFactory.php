<?php

namespace Drupal\test_helpers;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\Sql\Query;

/**
 * The Entity Storage Stub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
class QueryStubFactory implements QueryStubFactoryInterface {

  /**
   * Constructs a QueryStubFactory object.
   */
  public function __construct() {
  }

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
  public function get(EntityTypeInterface $entityType = NULL, string $conjunction = 'AND', \Closure $executeFunction = NULL) {
    if ($executeFunction === NULL) {
      $executeFunction = function () {
        return [];
      };
    }

    if ($entityType === NULL) {
      $entityType = UnitTestHelpers::createMock(EntityTypeInterface::class);
    }

    $queryStub = UnitTestHelpers::createPartialMockWithConstructor(Query::class, [
      'execute',
    ], [$entityType, $conjunction, $this->dbConnection, $this->namespaces], [
      'stubCheckConditionsMatch',
    ]);

    UnitTestHelpers::bindClosureToClassMethod($executeFunction, $queryStub, 'execute');
    UnitTestHelpers::bindClosureToClassMethod(function (Condition $conditionsExpected, $onlyListed = FALSE): bool {
      return UnitTestHelpers::matchConditions($this->condition, $conditionsExpected, $onlyListed);
    }, $queryStub, 'stubCheckConditionsMatch');

    return $queryStub;
  }

}
