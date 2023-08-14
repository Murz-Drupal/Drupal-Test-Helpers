<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Query helper functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class QueryHelpersTest extends UnitTestCase {

  /**
   * @covers ::findQueryCondition
   */
  public function testFindQueryCondition() {
    TestHelpers::saveEntity(Node::class);
    $entityQuery = TestHelpers::service('entity_type.manager')
      ->getStorage('node')
      ->getQuery();

    $entityQuery->condition('nid', 1);
    $entityQuery->condition('title', 'Foo', '<>');
    $entityQuery->condition('title', 'Bar');
    $entityQuery->condition('uid', 42, '<>');

    $this->assertEquals([
      'field' => 'nid',
      'value' => 1,
      'operator' => NULL,
      'langcode' => NULL,
    ], TestHelpers::findQueryCondition($entityQuery, 'nid'));

    $this->assertEquals([
      'field' => 'title',
      'value' => 'Foo',
      'operator' => '<>',
      'langcode' => NULL,
    ], TestHelpers::findQueryCondition($entityQuery, ['value' => 'Foo']));

    $this->assertEquals([
      'field' => 'title',
      'value' => 'Foo',
      'operator' => '<>',
      'langcode' => NULL,
    ], TestHelpers::findQueryCondition($entityQuery, ['operator' => '<>']));

    $this->assertEquals([
      [
        'field' => 'title',
        'value' => 'Foo',
        'operator' => '<>',
        'langcode' => NULL,
      ], [
        'field' => 'title',
        'value' => 'Bar',
        'operator' => NULL,
        'langcode' => NULL,
      ],
    ], TestHelpers::findQueryCondition($entityQuery, 'title', TRUE));

    $this->assertEquals([
      [
        'field' => 'title',
        'value' => 'Foo',
        'operator' => '<>',
        'langcode' => NULL,
      ], [
        'field' => 'uid',
        'value' => 42,
        'operator' => '<>',
        'langcode' => NULL,
      ],
    ], TestHelpers::findQueryCondition($entityQuery, ['operator' => '<>'], TRUE));

    $this->assertNull(TestHelpers::findQueryCondition($entityQuery, 'nid.value'));
  }

}
