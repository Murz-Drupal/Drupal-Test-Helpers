<?php

namespace src\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\UnitTestCaseWrapper;
use Drupal\test_helpers\UnitTestHelpers;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;
use Drupal\Tests\UnitTestCase;

/**
 * Class tests TestHelpersExampleController with Test Helpers API.
 *
 * @coversDefaultClass Drupal\test_helpers_example\Controller\TestHelpersExampleController
 * @group test_helpers_example
 */
class TestHelpersExampleControllerModernTest extends UnitTestCase {

  /**
   * @covers ::articlesList
   */
  public function testArticlesList() {
    ($node1 = UnitTestHelpers::createEntityStub(Node::class, ['title' => 'Article 1']))->save();
    ($node2 = UnitTestHelpers::createEntityStub(Node::class, ['title' => 'Article 2']))->save();

    UnitTestHelpers::getServiceStub('entity.query.sql')->stubSetExecuteHandler(function () use ($node1, $node2) {
      UnitTestCaseWrapper::assertTrue(UnitTestHelpers::queryIsSubsetOf($this, \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'article')
        ->sort('created', 'DESC')
        ->range(0, 3)));
      return [$node1->id(), $node2->id()];
    });

    $result = (new TestHelpersExampleController())->articlesList();

    $this->assertEquals('Article 1 (1)', $result['#items'][0]->getText());
    $this->assertEquals('Article 2 (2)', $result['#items'][1]->getText());
  }

}
