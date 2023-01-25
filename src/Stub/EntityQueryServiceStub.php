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
        $storage = UnitTestHelpers::service('entity_type.manager')->getStorage($this->entityTypeId);
        $allEntities = $storage->loadMultiple();
        foreach ($allEntities as $entity) {
          foreach ($this->condition->conditions() as $condition) {
            if (!UnitTestHelpers::matchEntityCondition($entity, $condition)) {
              continue 2;
            }
          }
          $resultEntities[] = $entity;
          // $result[] = $entity->id();
        }
        if ($this->sort) {
          $sortList = array_reverse($this->sort);
          foreach ($sortList as $rule) {
            usort($resultEntities, function ($a, $b) use ($rule) {
              if ($rule['direction'] == 'DESC') {
                $val2 = $a->{$rule['field']}->value ?? NULL;
                $val1 = $b->{$rule['field']}->value ?? NULL;
              }
              else {
                $val1 = $a->{$rule['field']}->value ?? NULL;
                $val2 = $b->{$rule['field']}->value ?? NULL;
              }
              if (is_string($val1) && is_string($val1)) {
                // For now let's use default comparison. It's not easy to detect
                // right string comparison rules, because it depends a lot on
                // database's field configuration.
                // If it doesn't fit for some tests - just use stubExecute to
                // check the conditions manually and hardcode the result.
                return strcmp($val1, $val2);
              }
              else {
                return $val1 <=> $val2;
              }
            });
          }
        }
        if ($this->range) {
          $resultEntities = array_slice($resultEntities, $this->range['start'], $this->range['length']);
        }
        $result = [];
        foreach ($resultEntities as $entity) {
          $result[$entity->id()] = $entity->id();
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
