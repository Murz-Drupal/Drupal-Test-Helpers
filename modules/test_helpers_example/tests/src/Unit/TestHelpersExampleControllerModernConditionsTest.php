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
    UnitTestHelpers::service('config.factory')->stubSetConfig('test_helpers_example', ['articles_to_display' => 2]);
    UnitTestHelpers::service('date.formatter')->stubSetFormat('medium', 'Medium', 'd.m.Y');
    // Putting coding standarts ignore flag to suppress warnings,
    // because here one-line arrays are more convenient.
    // @codingStandardsIgnoreStart
    UnitTestHelpers::saveEntityStub(Node::class, ['title' => 'Article 1', 'created' => '1672574400']);
    UnitTestHelpers::saveEntityStub(Node::class, ['title' => 'Article 2', 'created' => '1672660800']);
    // @codingStandardsIgnoreEnd

    UnitTestHelpers::getServiceStub('entity.query.sql')->stubSetExecuteHandler(function () {
      UnitTestCaseWrapper::assertTrue(UnitTestHelpers::queryIsSubsetOf($this, \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'article')
        ->sort('created', 'DESC')
        ->range(0, 2)));
      return ['1', '2'];
    });

    $result = UnitTestHelpers::createService(TestHelpersExampleController::class)->articlesList();

    $this->assertEquals('Article 1 (01.01.2023)', $result['#items'][0]->getText());
    $this->assertEquals('Article 2 (02.01.2023)', $result['#items'][1]->getText());
  }

}
