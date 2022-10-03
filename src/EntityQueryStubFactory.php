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
    $this->unitTestCaseApi = UnitTestCaseApi::getInstance();
    $this->unitTestHelpers = UnitTestHelpers::getInstance();
    /** @var \Drupal\Tests\Core\Database\Stub\StubPDO|\PHPUnit\Framework\MockObject\MockObject $pdoMock */
    $pdoMock = $this->unitTestCaseApi->createMock(StubPDO::class);
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
      $entityType = $this->unitTestCaseApi->createMock(EntityTypeInterface::class);
    }

    $queryStub = $this->unitTestHelpers->createPartialMockWithCostructor(Query::class, [
      'execute',
    ], [$entityType, $conjunction, $this->dbConnection, $this->namespaces], [
      'stubCheckConditionsMatch',
    ]);

    UnitTestHelpers::bindClosureToClassMethod($executeFunction, $queryStub, 'execute');
    UnitTestHelpers::bindClosureToClassMethod(function (Condition $conditionsExpected, $onlyListed = FALSE) {
      return EntityQueryStubFactory::matchConditions($conditionsExpected, $this->condition, $onlyListed);
    }, $queryStub, 'stubCheckConditionsMatch');

    return $queryStub;
  }

  /**
   * Performs matching of passed conditions with the query.
   */
  public static function matchConditions(Condition $conditionsExpectedObject, Condition $conditionsObject, $onlyListed = FALSE): bool {
    if (strcasecmp($conditionsObject->getConjunction(), $conditionsExpectedObject->getConjunction()) != 0) {
      return FALSE;
    }
    $conditions = $conditionsObject->conditions();
    $conditionsExpected = $conditionsExpectedObject->conditions();
    $conditionsFound = [];
    foreach ($conditions as $condition) {
      foreach ($conditionsExpected as $delta => $conditionExpected) {
        if (EntityQueryStubFactory::matchCondition($conditionExpected, $condition, $onlyListed)) {
          $conditionsFound[$delta] = TRUE;
        }
      }
    }
    if (count($conditionsFound) != count($conditionsExpected)) {
      return FALSE;
    }
    if ($onlyListed && (count($conditions) != count($conditionsExpected))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Performs matching of a single condition with expected.
   */
  public static function matchCondition(array $conditionExpected, array $conditionExists, $onlyListed = FALSE): bool {
    if (is_object($conditionExists['field'] ?? NULL)) {
      if (!is_object($conditionExpected['field'] ?? NULL)) {
        return FALSE;
      }
      return self::matchConditions($conditionExpected['field'], $conditionExists['field'], $onlyListed);
    }
    if (($conditionExpected['field'] ?? NULL) != ($conditionExists['field'] ?? NULL)) {
      return FALSE;
    }
    if (($conditionExpected['value'] ?? NULL) != ($conditionExists['value'] ?? NULL)) {
      return FALSE;
    }
    if (($conditionExpected['operator'] ?? NULL) != ($conditionExists['operator'] ?? NULL)) {
      return FALSE;
    }
    if (($conditionExpected['langcode'] ?? NULL) != ($conditionExists['langcode'] ?? NULL)) {
      return FALSE;
    }
    return TRUE;
  }

}
