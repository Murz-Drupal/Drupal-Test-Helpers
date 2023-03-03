<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;

/**
 * Tests EntityQueryServiceStubTest class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\EntityQueryServiceStub
 * @group test_helpers
 */
class EntityQueryServiceStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::get
   */
  public function testMatchingConditions() {
    /** @var \Drupal\test_helpers\EntityTypeManagerStubInterface $entityTypeManager */
    $entityTypeManager = TestHelpers::getServiceStub('entity_type.manager');
    $entityTypeManager->stubGetOrCreateStorage(Node::class);

    // Creating a custom function to generate the query result.
    $titleValues = ['Title 1', 'Title 2'];
    $entityQueryTestResult = ['1', '42'];
    /** @var \Drupal\Tests\test_helpers\Unit\EntityQueryServiceStubTest $testClass */
    $testClass = $this;
    /** @var \Drupal\test_helpers\Stub\EntityQueryServiceStub $entityQuerySql */
    $entityQuerySql = \Drupal::service('entity.query.sql');
    $entityQuerySql->stubSetExecuteHandler(function () use ($entityQueryTestResult, $titleValues, $testClass) {
      /** @var \Drupal\Core\Database\Query\SelectInterface|\Drupal\test_helpers\QueryStubItemInterface $this */
      // Checking that mandatory conditions are present in the query.
      $conditionsMandatory = $this->andConditionGroup();
      $conditionsMandatory->condition('title', $titleValues, 'IN');
      $conditionsMandatory->condition('field_category', 2);
      $orConditionGroup = $this->orConditionGroup();
      $orConditionGroup->condition('field_color', 'red');
      $orConditionGroup->condition('field_style', 'modern');
      $conditionsMandatory->condition($orConditionGroup);
      $testClass->assertTrue($this->stubCheckConditionsMatch($conditionsMandatory));

      // Checking onlyListed mode returns false, when we have more conditions.
      $testClass->assertFalse($this->stubCheckConditionsMatch($conditionsMandatory, TRUE));

      // Checking onlyListed mode returns true with exact conditions list.
      $orConditionGroup->condition('field_size', 'XL');
      $testClass->assertTrue($this->stubCheckConditionsMatch($conditionsMandatory, TRUE));

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
    }, 'node');

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

  /**
   * Tests conditions API.
   */
  public function testConditions() {
    TestHelpers::createEntity(Node::class, [
      // The id should be: 1.
      'title' => 'Node 1',
      'bundle' => '400',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      // The id should be: 2.
      'title' => 'Node 2',
      'bundle' => '300',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      // The id should be: 100.
      'title' => 'Node 3',
      'bundle' => '100',
      'nid' => '100',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      // The id should be: 101.
      'title' => NULL,
      'bundle' => '200',
    ])->save();

    $this->assertSame(self::genId([2]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('title', 'Node 2')
      ->execute());

    $this->assertSame(self::genId([1, 2]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('title', ['Node 2', 'Node 1'], 'IN')
      ->execute());

    $this->assertSame(self::genId([1, 2]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('title', ['Node 2', 'Node 1'], 'IN')
      ->execute());

    $this->assertSame(self::genId([100, 101]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('title', ['Node 2', 'Node 1'], 'NOT IN')
      ->execute());

    $this->assertSame(self::genId([100]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('bundle', 200, '<')
      ->execute());

    $this->assertSame(self::genId([100, 101]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('bundle', 200, '<=')
      ->execute());

    $this->assertSame(self::genId([1, 2]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('bundle', 200, '>')
      ->execute());

    $this->assertSame(self::genId([1, 2, 101]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('bundle', 200, '>=')
      ->execute());

    $this->assertSame(self::genId([1, 2, 100]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('bundle', 200, '<>')
      ->execute());

    $this->assertSame(self::genId([101]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('title', NULL, 'IS NULL')
      ->execute());

    $this->assertSame(self::genId([1, 2, 100]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('title', NULL, 'IS NOT NULL')
      ->execute());
  }

  /**
   * Tests range API.
   */
  public function testRange() {
    TestHelpers::createEntity(Node::class, [
      // The id should be: 1.
      'title' => 'Node 1',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      // The id should be: 2.
      'title' => 'Node 2',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      // The id should be: 100.
      'title' => 'Node 3',
      'nid' => '100',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      // The id should be: 101.
      'title' => NULL,
    ])->save();
    TestHelpers::createEntity(Node::class, [
      // The id should be: 102.
      'title' => 'Node 5',
    ])->save();

    $this->assertSame(self::genId([2, 100]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->range(1, 2)
      ->execute());

    $this->assertSame(self::genId([101]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('nid', 100, '>=')
      ->range(1, 1)
      ->execute());
  }

  /**
   * Tests sort API.
   */
  public function testSort() {
    $options = [
      'fields' => [
        'field_integer1' => 'integer',
        'field_integer2' => 'integer',
        'field_string1' => 'string',
      ],
    ];
    TestHelpers::createEntity(Node::class, [
      'field_integer1' => 100,
      'field_integer2' => 100,
      'field_string1' => '100',
    ], NULL, $options)->save();
    TestHelpers::createEntity(Node::class, [
      'field_integer1' => 101,
      'field_integer2' => 100,
      'field_string1' => '0',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      'field_integer1' => 10,
      'field_integer2' => 10,
      'field_string1' => '10',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      'field_integer1' => 0,
      'field_integer2' => 0,
      'field_string1' => '11',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      'field_integer1' => -1,
      'field_string1' => '111',
    ])->save();
    TestHelpers::createEntity(Node::class, [
      'field_integer1' => -10,
      'field_integer2' => -10,
      'field_string1' => '-111',
    ])->save();

    $this->assertSame(self::genId([6, 5, 4, 3, 1, 2]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->sort('field_integer1')
      ->execute());

    $this->assertSame(self::genId([2, 1, 3, 4, 5, 6]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->sort('field_integer1', 'DESC')
      ->execute());

    $this->assertSame(self::genId([2, 1, 3, 4, 6, 5]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->sort('field_integer2', 'DESC')
      ->sort('field_integer1', 'DESC')
      ->execute());

    $this->assertSame(self::genId([6, 2, 3, 1, 4, 5]), \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      ->sort('field_string1')
      ->execute());
  }

  /**
   * Tests general execute() API.
   */
  public function testEntityQueryExecute() {
    // Putting coding standards ignore flag to suppress warnings,
    // because here one-line arrays are more convenient.
    // @codingStandardsIgnoreStart
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A1', 'status' => '1', 'created' => '1672574400']);
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A2', 'status' => '1', 'created' => '1672660800']);
    TestHelpers::saveEntity(Node::class, ['type' => 'page', 'title' => 'P1', 'status' => '0', 'created' => '1672747200']);
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A3', 'status' => '0', 'created' => '1672833600']);
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A4', 'status' => '1', 'created' => '1672833600']);
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A5', 'status' => '1', 'created' => '1672833600']);
    // @codingStandardsIgnoreEnd

    $query = \Drupal::service('entity_type.manager')->getStorage('node')
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, 3);

    $result = $query->execute();

    $this->assertEquals(['5' => '5', '6' => '6', '2' => '2'], $result);
  }

  /**
   * Generates a keyed array with strings from numeric array.
   *
   * @param int[] $ids
   *   The list of integer ids.
   *
   * @return string[]
   *   The keyed array with strings.
   */
  protected function genId(array $ids) {
    foreach ($ids as $id) {
      $idString = (string) $id;
      $result[$idString] = $idString;
    }
    return $result;
  }

}
