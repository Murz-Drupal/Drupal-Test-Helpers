<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\test_helpers\StubFactory\EntityQueryStubFactory;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * A stub of the Drupal's default QueryFactoryInterface class.
 */
class EntityQueryServiceStub implements QueryFactoryInterface {

  /**
   * Constructs a new EntityQueryServiceStub.
   */
  public function __construct() {
    $this->queryStubFactory = new EntityQueryStubFactory();
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->namespaces[] = 'Drupal\Core\Entity\Query\Sql';
  }

  /**
   * Gets the query for entity type.
   */
  public function get($entityType, $conjunction) {
    $executeFunction =
      $this->executeFunctions[$entityType->id()]
      ?? $this->executeFunctions['all']
      ?? function () {
        $result = [];
        $storage = UnitTestHelpers::addService('entity_type.manager')->getStorage($this->entityTypeId);
        $allEntities = $storage->loadMultiple();
        foreach ($allEntities as $entity) {
          foreach ($this->condition->conditions() as $condition) {
            if (!UnitTestHelpers::matchEntityCondition($entity, $condition)) {
              continue 2;
            }
          }
          $result[] = $entity->id();
        }

        return $result;
      };
    $query = $this->queryStubFactory->get($entityType, $conjunction, $executeFunction);
    return $query;
  }

  /**
   * Gets the aggregate query for entity type.
   */
  public function getAggregate($entityType, $conjunction) {
    // @todo Implement a custom getAggregate call.
    return $this->get($entityType, $conjunction);
  }

  /**
   * Adds an execute callback function to the particular entity type.
   */
  public function stubSetExecuteHandler(callable $function, string $entityTypeId = 'all') {
    $this->executeFunctions[$entityTypeId] = $function;
  }

  public function stubCheckConditionsMatch(ConditionInterface $conditionsExpected, $onlyListed = FALSE) {
    return UnitTestHelpers::matchConditions($this->condition, $conditionsExpected, $onlyListed);
  }

}
