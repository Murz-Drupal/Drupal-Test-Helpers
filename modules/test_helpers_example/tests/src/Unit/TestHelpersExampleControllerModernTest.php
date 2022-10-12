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

    UnitTestHelpers::getServiceStub('entity.query.sql')->stubAddExecuteHandler(function () use ($node1, $node2) {
      UnitTestCaseWrapper::assertTrue($this->stubCheckConditionsMatch($this->andConditionGroup()
        ->condition('status', 1)
        ->condition('type', 'article')));
      UnitTestCaseWrapper::assertTrue(UnitTestHelpers::isNestedArraySubsetOf($this->sort[0], [
        'field' => 'created',
        'direction' => 'DESC',
      ]));
      UnitTestCaseWrapper::assertEquals(['start' => 0, 'length' => 3], $this->range);
      return [$node1->id(), $node2->id()];
    });

    $result = (new TestHelpersExampleController())->articlesList();

    $this->assertEquals('Article 1 (1)', $result['#items'][0]->getText());
    $this->assertEquals('Article 2 (2)', $result['#items'][1]->getText());
  }

}
