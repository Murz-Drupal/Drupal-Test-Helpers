<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Tests\UnitTestCase;

/**
 * The EntityQueryServiceStub class.
 *
 * A stub for class Drupal\Core\Entity\Query\Sql\QueryFactory.
 */
class EntityQueryServiceStub extends UnitTestCase implements QueryFactoryInterface {

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
    $entityTypeId = $entityType->id();
    if (isset($this->executeFunctions[$entityTypeId][$conjunction])) {
      $executeFunction = $this->executeFunctions[$entityTypeId][$conjunction];
    }
    else {
      $executeFunction = function () {
        return [];
      };
    }
    $query = $this->queryStubFactory->get($entityType, $conjunction, $executeFunction);
    return $query;
  }

  /**
   * Gets the aggregate query for entity type.
   */
  public function getAggregate($entityType, $conjunction) {
    $entityTypeId = $entityType->id();
    if (isset($this->executeFunctions[$entityTypeId][$conjunction])) {
      $executeFunction = $this->executeFunctions[$entityTypeId][$conjunction];
    }
    else {
      $executeFunction = function () {
        return [];
      };
    }
    // @todo Implement a getAggregate call.
    $query = $this->queryStubFactory->get($entityType, $conjunction, $executeFunction);
    return $query;
  }

  /**
   * Adds an execute callback function to the particular entity type.
   */
  public function stubAddExecuteFunction(string $entityTypeId, string $conjunction, callable $function) {
    $this->executeFunctions[$entityTypeId][$conjunction] = $function;
  }

}
