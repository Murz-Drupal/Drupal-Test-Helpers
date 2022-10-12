<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\Sql\Condition;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * The EntityQueryStub factory.
 */
class EntityQueryStubFactory {

  /**
   * Constructs a QueryStubFactory object.
   */
  public function __construct() {
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->namespaces[] = 'Drupal\Core\Entity\Query\Sql';
    /** @var \Drupal\Tests\Core\Database\Stub\StubPDO|\PHPUnit\Framework\MockObject\MockObject $pdoMock */
    $pdoMock = UnitTestHelpers::createMock(StubPDO::class);
    $this->dbConnection = new StubConnection($pdoMock, []);
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
    UnitTestHelpers::bindClosureToClassMethod(function (Condition $conditionsExpected, $onlyListed = FALSE) {
      return UnitTestHelpers::matchConditions($this->condition, $conditionsExpected, $onlyListed);
    }, $queryStub, 'stubCheckConditionsMatch');

    return $queryStub;
  }

}
