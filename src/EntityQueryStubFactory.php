<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\Sql\Condition;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * The Entity Storage Stub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
class EntityQueryStubFactory {

  /**
   * Constructs a QueryStubFactory object.
   */
  public function __construct() {
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->namespaces[] = 'Drupal\Core\Entity\Query\Sql';
    $this->unitTestHelpers = UnitTestHelpers::getInstance();
    /** @var \Drupal\Tests\Core\Database\Stub\StubPDO|\PHPUnit\Framework\MockObject\MockObject $pdoMock */
    $pdoMock = $this->unitTestHelpers->createMock(StubPDO::class);
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
      $entityType = $this->unitTestHelpers->createMock(EntityTypeInterface::class);
    }

    $queryStub = $this->unitTestHelpers->createPartialMockWithCostructor(Query::class, [
      'execute',
    ], [$entityType, $conjunction, $this->dbConnection, $this->namespaces], [
      'stubCheckConditionsMatch',
    ]);

    UnitTestHelpers::bindClosureToClassMethod($executeFunction, $queryStub, 'execute');
    UnitTestHelpers::bindClosureToClassMethod(function (Condition $conditionsExpected, $onlyListed = FALSE) {
      return UnitTestHelpers::matchConditions($conditionsExpected, $this->condition, $onlyListed);
    }, $queryStub, 'stubCheckConditionsMatch');

    return $queryStub;
  }

}
