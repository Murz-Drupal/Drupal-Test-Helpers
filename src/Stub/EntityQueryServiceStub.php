<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\test_helpers\StubFactory\EntityQueryStubFactory;
use Drupal\test_helpers\TestHelpers;

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
   * Returns the base execute function closure.
   */
  public static function stubGetExecuteBaseFunction() {
    $executeBaseFunction = function () {
      $result = [];
      $storage = TestHelpers::service('entity_type.manager')->getStorage($this->entityTypeId);
      if ($this->latestRevision ?? NULL) {
        $allEntities = $storage->stubGetAllLatestRevision();
      }
      else {
        $allEntities = $storage->loadMultiple();
      }

      $resultEntities = [];
      foreach ($allEntities as $entity) {
        foreach ($this->condition->conditions() as $condition) {
          // SqlContentEntityStorage::buildPropertyQuery() adds a strange
          // condition to check that default_langcode = 1, here we just skip it.
          // @todo Investiage it deeper.
          if ($condition['field'] == 'default_langcode' && $condition['value'] === [1]) {
            continue 1;
          }
          if (!TestHelpers::matchEntityCondition($entity, $condition)) {
            continue 2;
          }
        }
        $resultEntities[] = $entity;
      }

      if ($this->sort) {
        $sortList = array_reverse($this->sort);
        foreach ($sortList as $rule) {
          usort(
            $resultEntities,
            function ($a, $b) use ($rule) {
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
            }
          );
        }
      }
      if ($this->range) {
        $resultEntities = array_slice($resultEntities, $this->range['start'], $this->range['length']);
      }
      $result = [];
      foreach ($resultEntities as $entity) {
        if ($this->latestRevision ?? NULL) {
          $key = $entity->getRevisionId();
        }
        else {
          $key = $entity->id();
        }
        $result[$key] = $entity->id();

      }
      return $result;
    };
    return $executeBaseFunction;
  }

  /**
   * Gets the query for entity type.
   */
  public function get($entityType, $conjunction) {
    $executeFunction =
      $this->executeFunctions[$entityType->id()]
      ?? $this->executeFunctions['all']
      ?? $this->stubGetExecuteBaseFunction();
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
   * Sets the function to handle execute calls.
   *
   * You can call `$this->stubExecuteBase()` in your custom callback function
   * to execute the base stub behavior for the query.
   *
   * @param \Closure $executeFunction
   *   The execute function.
   * @param string $entityTypeId
   *   The entity type to attach, all entity types by default.
   */
  public function stubSetExecuteHandler(callable $function, string $entityTypeId = 'all') {
    $this->executeFunctions[$entityTypeId] = $function;
  }

  /**
   * Checks if the passed conditions matches to configured.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditionsExpected
   *   The expected conditions.
   * @param mixed $onlyListed
   *   Return FALSE if class contains more conditions than expected.
   *
   * @return bool
   *   TRUE if matchs, FALSE if not matchs.
   */
  public function stubCheckConditionsMatch(ConditionInterface $conditionsExpected, $onlyListed = FALSE) {
    return TestHelpers::matchConditions($this->condition, $conditionsExpected, $onlyListed);
  }

}
