<?php

namespace src\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\UnitTestCaseWrapper;
use Drupal\test_helpers\UnitTestHelpers;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests TestHelpersExampleController with Test Helpers API to check conditions.
 *
 * @coversDefaultClass Drupal\test_helpers_example\Controller\TestHelpersExampleController
 * @group test_helpers_example
 */
class TestHelpersExampleControllerModernConditionsTest extends UnitTestCase {

  /**
   * @covers ::articlesList
   */
  public function testArticlesList() {
    UnitTestHelpers::createEntityStub(Node::class, ['title' => 'Article 1'])->save();
    UnitTestHelpers::createEntityStub(Node::class, ['title' => 'Article 2'])->save();

    UnitTestHelpers::getServiceStub('entity.query.sql')->stubSetExecuteHandler(function () {
      UnitTestCaseWrapper::assertTrue(UnitTestHelpers::queryIsSubsetOf($this, \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'article')
        ->sort('title', 'DESC')
        ->range(0, 2)));
      return ['1', '2'];
    });

    $result = (new TestHelpersExampleController())->articlesList();

    $this->assertEquals('Article 1 (1)', $result['#items'][0]->getText());
    $this->assertEquals('Article 2 (2)', $result['#items'][1]->getText());
  }

}
