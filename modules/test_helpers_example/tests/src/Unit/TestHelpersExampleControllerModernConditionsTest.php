<?php

namespace src\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\UnitTestCaseWrapper;
use Drupal\test_helpers\TestHelpers;
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
    TestHelpers::service('config.factory')->stubSetConfig('test_helpers_example', ['articles_to_display' => 2]);
    TestHelpers::service('date.formatter')->stubSetFormat('medium', 'Medium', 'd.m.Y');
    // Putting coding standards ignore flag to suppress warnings until the
    // https://www.drupal.org/project/coder/issues/3185082 is fixed.
    // @codingStandardsIgnoreStart
    TestHelpers::saveEntity(Node::class, ['title' => 'Article 1', 'created' => '1672574400']);
    TestHelpers::saveEntity(Node::class, ['title' => 'Article 2', 'created' => '1672660800']);
    // @codingStandardsIgnoreEnd

    TestHelpers::getServiceStub('entity.query.sql')->stubSetExecuteHandler(function () {
      UnitTestCaseWrapper::assertTrue(TestHelpers::queryIsSubsetOf($this, \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'article')
        ->sort('created', 'DESC')
        ->range(0, 2)));
      return ['1', '2'];
    });

    $result = TestHelpers::createClass(TestHelpersExampleController::class)->articlesList();

    $this->assertEquals('Article 1 (01.01.2023)', $result['#items'][0]->getText());
    $this->assertEquals('Article 2 (02.01.2023)', $result['#items'][1]->getText());
  }

}
