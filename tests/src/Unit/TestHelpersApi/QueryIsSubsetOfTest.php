<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;

/**
 * Tests Query helper functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class QueryIsSubsetOfTest extends UnitTestCase {

  /**
   * @covers ::queryIsSubsetOf
   */
  public function testFindQueryCondition() {
    TestHelpers::saveEntity(Node::class);
    TestHelpers::saveEntity(Term::class);
    $query1 = $this->getQuery()
      ->condition('nid', 1)
      ->condition('title', 'Foo', '<>')
      ->condition('title', 'Bar')
      ->condition('uid', 42, '<>');
    $query2 = $this->getQuery('taxonomy_term')
      ->accessCheck()
      ->condition('title', 'Foo')
      ->sort('title')
      ->range(1, 3);
    $query3 = $this->getQuery()
      ->accessCheck(FALSE)
      ->condition('title', 'Foo')
      ->sort('nid', 'DESC')
      ->range(5);

    $query4 = $this->getQuery();
    $query4CG1 = $query4->andConditionGroup()
      ->condition('nid', 123, '<>')
      ->condition('nid', 456, '<>')
      ->condition('nid', '789', '<>');
    $query4CG2 = $query4->orConditionGroup()
      ->condition('title', 'Foo')
      ->condition('title', 'Bar');
    $query4CG2subCG3 = $query4->orConditionGroup()
      ->condition('status', '1')
      ->condition('status', '0');
    $query4CG2->condition($query4CG2subCG3);
    $query4
      ->condition('sticky', 1)
      ->condition($query4CG1)
      ->condition($query4CG2);

    $query5 = $this->getQuery();
    $query5CG1 = $query5->andConditionGroup()
      ->condition('nid', 123, '<>')
      ->condition('nid', 456, '<>')
      ->condition('nid', '789', '<>');
    $query5CG2 = $query5->orConditionGroup()
      ->condition('title', 'Foo')
      ->condition('title', 'Bar');
    $query5CG2subCG3 = $query5->orConditionGroup()
      ->condition('status', '2')
      ->condition('status', '0');
    $query5CG2->condition($query5CG2subCG3);
    $query5
      ->condition('sticky', 1)
      ->condition($query5CG1)
      ->condition($query5CG2);

    // The exact queries.
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query1, $query1));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query2, $query2));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query3, $query3));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query1,
      $this->getQuery()
        ->condition('uid', 42, '<>')
        ->condition('title', 'Bar')
        ->condition('nid', 1)
        ->condition('title', 'Foo', '<>')
    ));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query4, $query4));

    // The complex query with differs inside condition groups.
    try {
      TestHelpers::queryIsSubsetOf($query4, $query5);
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The expected condition group " or " is not matching, items: array (
  0 =>
  array (
    'field' => 'title',
    'value' => 'Foo',
    'operator' => NULL,
    'langcode' => NULL,
  ),
  1 =>
  array (
    'field' => 'title',
    'value' => 'Bar',
    'operator' => NULL,
    'langcode' => NULL,
  ),
  2 =>
  array (
    'field' => '[orConditionGroup with 2 items]',
    'value' => NULL,
    'operator' => NULL,
    'langcode' => NULL,
  ),
)", preg_replace('/=>\s\n/', "=>\n", $e->getMessage()));
    }

    // The subquery.
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query1,
      $this->getQuery()
        ->condition('uid', 42, '<>')
        ->condition('nid', 1)
        ->condition('title', 'Foo', '<>')
    ));

    // The subquery with strict match.
    try {
      TestHelpers::queryIsSubsetOf($query1,
        $this->getQuery()
          ->condition('uid', 42, '<>')
          ->condition('nid', 1)
          ->condition('title', 'Foo', '<>'),
        TRUE
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The condition is not listed in expected: array (
  'field' => 'title',
  'value' => 'Bar',
  'operator' => NULL,
  'langcode' => NULL,
)", $e->getMessage());
    }

    // Wrong condition case 1.
    try {
      TestHelpers::queryIsSubsetOf($query1,
        $this->getQuery()
          ->condition('nid', 1)
          ->condition('title', 'Foo', '<>')
          ->condition('title', 'Bar')
          ->condition('uid', 42, '='),
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The expected condition is not found: array (
  'field' => 'uid',
  'value' => 42,
  'operator' => '=',
  'langcode' => NULL,
)", $e->getMessage());
    }

    // Wrong condition case 2.
    try {
      TestHelpers::queryIsSubsetOf($query1,
        $this->getQuery()
          ->condition('nid', 2)
          ->condition('title', 'Foo', '<>')
          ->condition('uid', 42, '='),
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The expected condition is not found: array (
  'field' => 'nid',
  'value' => 2,
  'operator' => NULL,
  'langcode' => NULL,
)", $e->getMessage());
    }

    // Right accessCheck.
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query2,
      $this->getQuery('taxonomy_term')
        ->condition('title', 'Foo')
    ));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query2,
      $this->getQuery('taxonomy_term')
        ->accessCheck(TRUE)
        ->condition('title', 'Foo')
    ));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query3,
      $this->getQuery()
        ->accessCheck(FALSE)
        ->condition('title', 'Foo')
    ));

    // Wrong accessCheck.
    try {
      TestHelpers::queryIsSubsetOf($query1,
        $this->getQuery()
          ->accessCheck()
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The accessCheck doesn't match, expected: true, actual: NULL", $e->getMessage());
    }

    try {
      TestHelpers::queryIsSubsetOf($query2,
        $this->getQuery('taxonomy_term')
          ->accessCheck(FALSE)
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The accessCheck doesn't match, expected: false, actual: true", $e->getMessage());
    }

    try {
      TestHelpers::queryIsSubsetOf($query3,
        $this->getQuery()
          ->accessCheck(TRUE)
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The accessCheck doesn't match, expected: true, actual: false", $e->getMessage());
    }

    // Right sort.
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query2,
      $this->getQuery('taxonomy_term')
        ->sort('title')
    ));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query2,
      $this->getQuery('taxonomy_term')
        ->sort('title', 'ASC')
    ));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query3,
      $this->getQuery()
        ->sort('nid', 'DESC')
    ));

    // Wrong sort.
    try {
      TestHelpers::queryIsSubsetOf($query2,
        $this->getQuery('taxonomy_term')
          ->sort('nid')
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The sort doesn't match, expected: array (
  0 =>
  array (
    'field' => 'nid',
    'direction' => 'ASC',
    'langcode' => NULL,
  ),
), actual: array (
  0 =>
  array (
    'field' => 'title',
    'direction' => 'ASC',
    'langcode' => NULL,
  ),
)", preg_replace('/=>\s\n/', "=>\n", $e->getMessage()));
    }

    try {
      TestHelpers::queryIsSubsetOf($query2,
        $this->getQuery('taxonomy_term')
          ->sort('title', 'DESC')
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The sort doesn't match, expected: array (
  0 =>
  array (
    'field' => 'title',
    'direction' => 'DESC',
    'langcode' => NULL,
  ),
), actual: array (
  0 =>
  array (
    'field' => 'title',
    'direction' => 'ASC',
    'langcode' => NULL,
  ),
)", preg_replace('/=>\s\n/', "=>\n", $e->getMessage()));
    }

    // Right range.
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query2,
      $this->getQuery('taxonomy_term')
        ->range(1, 3)
    ));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query3,
      $this->getQuery()
        ->range(5)
    ));
    $this->assertTrue(TestHelpers::queryIsSubsetOf($query3,
      $this->getQuery()
        ->range(5, NULL)
    ));

    // Wrong range.
    try {
      TestHelpers::queryIsSubsetOf($query2,
        $this->getQuery('taxonomy_term')
          ->range(1, 4)
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The range doesn't match, expected: array (
  'start' => 1,
  'length' => 4,
), actual: array (
  'start' => 1,
  'length' => 3,
)", preg_replace('/=>\s\n/', "=>\n", $e->getMessage()));
    }

    try {
      TestHelpers::queryIsSubsetOf($query2,
        $this->getQuery('taxonomy_term')
          ->range(1)
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The range doesn't match, expected: array (
  'start' => 1,
  'length' => NULL,
), actual: array (
  'start' => 1,
  'length' => 3,
)", preg_replace('/=>\s\n/', "=>\n", $e->getMessage()));
    }

    try {
      TestHelpers::queryIsSubsetOf($query3,
        $this->getQuery()
          ->range(5, 1)
      );
    }
    catch (\Exception $e) {
      $this->assertEquals(1024, $e->getCode());
      $this->assertEquals("The range doesn't match, expected: array (
  'start' => 5,
  'length' => 1,
), actual: array (
  'start' => 5,
  'length' => NULL,
)", preg_replace('/=>\s\n/', "=>\n", $e->getMessage()));
    }

  }

  /**
   * Gets an entity query.
   *
   * @param string $entityType
   *   An entity type.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery for the given entity type.
   */
  private function getQuery(string $entityType = 'node'): QueryInterface {
    return TestHelpers::service('entity_type.manager')
      ->getStorage($entityType)
      ->getQuery();
  }

}
