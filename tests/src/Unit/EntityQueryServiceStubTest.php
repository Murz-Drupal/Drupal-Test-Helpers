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
    $this->unitTestHelpers = UnitTestHelpers::getInstance();
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
      // Checking that mandatory conditions are present in the query.
      $conditionsMandatory = $this->andConditionGroup();
      $conditionsMandatory->condition('title', $titleValues, 'IN');
      $conditionsMandatory->condition('field_category', 2);
      $orConditionGroup = $this->orConditionGroup();
      $orConditionGroup->condition('field_color', 'red');
      $orConditionGroup->condition('field_style', 'modern');
      $conditionsMandatory->condition($orConditionGroup);
      $testClass->assertTrue($this->stubCheckConditionsMatch($conditionsMandatory));

      // Checking onlyListed mode returns false, because we have more conditions.
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatory, TRUE));

      // Checking that wrong conditions check is return FALSE.
      $conditionsMandatoryWrong1 = $this->orConditionGroup();
      $conditionsMandatoryWrong1->condition('title', $titleValues, 'IN');
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatoryWrong1));

      $conditionsMandatoryWrong2 = $this->andConditionGroup();
      $conditionsMandatoryWrong2->condition('title', $titleValues, 'NOT IN');
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatoryWrong2));

      $conditionsMandatoryWrong3 = $this->andConditionGroup();
      $conditionsMandatoryWrong3->condition('title', [], 'IN');
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatoryWrong3));

      $conditionsMandatoryWrong4 = $this->andConditionGroup();
      $conditionsMandatoryWrong4->condition('field_category', 2, 'NOT IN');
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatoryWrong4));

      $conditionsMandatoryWrong5 = $this->andConditionGroup();
      $orConditionGroup = $this->orConditionGroup();
      $orConditionGroup->condition('field_color', 'blue');
      $conditionsMandatoryWrong5->condition($orConditionGroup);
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatoryWrong5));

      $conditionsMandatoryWrong6 = $this->andConditionGroup();
      $orConditionGroup = $this->andConditionGroup();
      $orConditionGroup->condition('field_color', 'red');
      $conditionsMandatoryWrong6->condition($orConditionGroup);
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatoryWrong6));

      // Returning a pre-defined result for the query.
      return $entityQueryTestResult;
    });

    $entityQuery = \Drupal::service('entity_type.manager')
      ->getStorage('node')
      ->getQuery();
    $entityQuery->condition('title', $titleValues, 'IN');
    $entityQuery->condition('field_category', 2);
    $orConditionGroup = $entityQuery->orConditionGroup();
    $orConditionGroup->condition('field_color', 'red');
    $orConditionGroup->condition('field_style', 'modern');
    $orConditionGroup->condition('field_size', 'XL');
    $entityQuery->condition($orConditionGroup);
    $result = $entityQuery->execute();

    $this->assertSame($entityQueryTestResult, $result);
  }

}
