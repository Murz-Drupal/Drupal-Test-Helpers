<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\EntityTypeManagerStubFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * Tests EntityQueryServiceStubTest class.
 *
 * @coversDefaultClass \Drupal\test_helpers\EntityStorageStub
 * @group test_helpers
 */
class EntityQueryServiceStubTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    (new EntityTypeManagerStubFactory())->create();
    $this->unitTestHelpers = new UnitTestHelpers();
  }

  /**
   * Tests creating an Entity Stub and storaga eactions.
   *
   * @covers ::__construct
   * @covers ::get
   */
  public function testEntityQueryService() {
    \Drupal::service('entity_type.manager')->stubGetOrCreateStorage(Node::class);

    // Creating a custom function to generate the query result.
    $titleValues = ['Title 1', 'Title 2'];
    $entityQueryTestResult = ['1', '42'];
    $testClass = $this;
    \Drupal::service('entity.query.sql')->stubAddExecuteFunction('node', 'AND', function () use ($entityQueryTestResult, $titleValues, $testClass) {
      $conditions = $this->condition->conditions();
      // Checking that conditions are successfully added to the query.
      $testClass->assertEquals($titleValues, $conditions[0]['value']);
      // Returning a pre-defined result for the query.
      return $entityQueryTestResult;
    });

    $entityQuery = \Drupal::service('entity_type.manager')
      ->getStorage('node')
      ->getQuery();
    $entityQuery->condition('title', $titleValues, 'IN');
    $entityQuery->condition('field_category', 2);
    $result = $entityQuery->execute();

    $this->assertSame($entityQueryTestResult, $result);
  }

}
