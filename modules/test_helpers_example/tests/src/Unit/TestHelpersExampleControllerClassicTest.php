<?php

namespace src\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;

/**
 * Class tests TestHelpersExampleController using a classic approach.
 *
 * @coversDefaultClass Drupal\test_helpers_example\Controller\TestHelpersExampleController
 * @group test_helpers_example
 */
class TestHelpersExampleControllerClassicTest extends UnitTestCase {

  /**
   * @covers ::articlesList
   */
  public function testArticlesList() {
    $entityQuery = $this->createMock(QueryInterface::class);
    $entityQuery->method('sort')
      ->willReturnCallback(
        function ($field, $direction = 'ASC', $langcode = NULL) use ($entityQuery) {
          $this->assertEquals('title', $field);
          $this->assertEquals('DESC', $direction);
          return $entityQuery;
        }
      );
    $entityQuery->method('range')
      ->willReturnCallback(
        function ($start = NULL, $length = NULL) use ($entityQuery) {
          $this->assertEquals(0, $start);
          $this->assertEquals(2, $length);
          return $entityQuery;
        }
      );
    $entityQuery->method('condition')
      ->willReturnCallback(
        function ($field, $value = NULL, $operator = NULL, $langcode = NULL) use ($entityQuery) {
          static $callsCount;
          if (!$callsCount) {
            $callsCount = 1;
          }
          else {
            $callsCount++;
          }
          switch ($callsCount) {
            case 1:
              $this->assertEquals('status', $field);
              $this->assertEquals(1, $value);
              $this->assertEquals(NULL, $operator);
              break;

            case 2:
              $this->assertEquals('type', $field);
              $this->assertEquals('article', $value);
              $this->assertEquals(NULL, $operator);
              break;
          }
          return $entityQuery;
        }
      );

    $entityQuery->method('execute')->willReturn(['1', '2']);

    $toLinkMock = function ($text) {
      $link = $this->createMock(Link::class);
      $link->method('getText')->willReturn($text);
      return $link;
    };

    $node1 = $this->createMock(NodeInterface::class);
    $node1->method('id')->willReturn('1');
    $node1->method('label')->willReturn('Article 1');
    $node1->method('toLink')->willReturnCallback($toLinkMock);

    $node2 = $this->createMock(NodeInterface::class);
    $node2->method('id')->willReturn('2');
    $node2->method('label')->willReturn('Article 2');
    $node2->method('toLink')->willReturnCallback($toLinkMock);

    $entityStorage = $this->createMock(EntityStorageInterface::class);
    $entityStorage->method('getQuery')->willReturn($entityQuery);
    $entityStorage->method('loadMultiple')->willReturn([$node1, $node2]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($entityStorage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($container);

    $controller = new TestHelpersExampleController();
    $result = $controller->articlesList();

    $this->assertEquals('Article 1 (1)', $result['#items'][0]->getText());
    $this->assertEquals('Article 2 (2)', $result['#items'][1]->getText());
  }

}
