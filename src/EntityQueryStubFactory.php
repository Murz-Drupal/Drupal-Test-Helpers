<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\UnitTestCase;

/**
 * The Entity Storage Stub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
class EntityQueryStubFactory extends UnitTestCase {

  /**
   * Constructs a QueryStubFactory object.
   */
  public function __construct() {
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->namespaces[] = 'Drupal\Core\Entity\Query\Sql';
    UnitTestHelpers::addToContainer('test_helpers.unit_test_helpers', new UnitTestHelpers());
  }

  /**
   * Instantiates an entity query for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   * @param callable $executeFunction
   *   The function to use for `execute` call.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query for a specific configuration entity type.
   */
  public function get(EntityTypeInterface $entity_type, string $conjunction, callable $executeFunction = NULL) {
    if ($executeFunction === NULL) {
      $executeFunction = function () {
        return [];
      };
    }
    $pdoMock = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $dbConnectionStub = new StubConnection($pdoMock, []);
    $queryStub = \Drupal::service('test_helpers.unit_test_helpers')->createPartialMockWithCostructor(Query::class, [
      'execute',
    ], [$entity_type, $conjunction, $dbConnectionStub, $this->namespaces], ['stubIsConditionsExist']);

    \Drupal::service('test_helpers.unit_test_helpers')::bindClosureToClassMethod($executeFunction, $queryStub, 'execute');
    \Drupal::service('test_helpers.unit_test_helpers')::bindClosureToClassMethod(function (array $conditionsExpected, $onlyListed = FALSE) {
      $compareConditions = function ($condition1, $condition2) {
        if (($condition1['field'] ?? NULL) != ($condition2['field'] ?? NULL)) {
          return FALSE;
        }
        if (($condition1['value'] ?? NULL) != ($condition2['value'] ?? NULL)) {
          return FALSE;
        }
        if (($condition1['operator'] ?? NULL) != ($condition2['operator'] ?? NULL)) {
          return FALSE;
        }
        if (($condition1['langcode'] ?? NULL) != ($condition2['langcode'] ?? NULL)) {
          return FALSE;
        }
        return TRUE;
      };

      $conditions = $this->condition->conditions();
      foreach ($conditions as $condition) {
        foreach ($conditionsExpected as $delta => $conditionExpected) {
          if ($compareConditions($condition, $conditionExpected)) {
            $conditionsFound[$delta] = TRUE;
          }
        }
      }
      if (count($conditionsFound) != count($conditionsExpected)) {
        return FALSE;
      }
      if ($onlyListed && count($conditions) != count($conditionsExpected)) {
        return FALSE;
      }
      return TRUE;
    }, $queryStub, 'stubIsConditionsExist');

    return $queryStub;
  }

}
