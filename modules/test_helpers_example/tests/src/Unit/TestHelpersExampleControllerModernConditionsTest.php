<?php

namespace Drupal\Tests\test_helpers_example\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\UnitTestCaseWrapper;
use Drupal\test_helpers\TestHelpers;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;

/**
 * Tests TestHelpersExampleController with Test Helpers API to check conditions.
 *
 * @coversDefaultClass \Drupal\test_helpers_example\Controller\TestHelpersExampleController
 * @group test_helpers_example
 */
class TestHelpersExampleControllerModernConditionsTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::create
   * @covers ::articlesList
   */
  public function testArticlesList() {
    TestHelpers::service('config.factory')->stubSetConfig('test_helpers_example.settings', ['articles_to_display' => 2]);
    TestHelpers::service('date.formatter')->stubSetFormat('medium', 'Medium', 'd.m.Y');
    TestHelpers::saveEntity(User::class, ['name' => 'Alice']);
    // Putting coding standards ignore flag to suppress warnings until the
    // https://www.drupal.org/project/coder/issues/3185082 is fixed.
    // @codingStandardsIgnoreStart
    TestHelpers::saveEntity(Node::class, ['title' => 'A1', 'uid' => 1, 'created' => 1672574400]);
    TestHelpers::saveEntity(Node::class, ['title' => 'A2', 'uid' => 1, 'created' => 1672660800]);
    // @codingStandardsIgnoreEnd

    TestHelpers::getServiceStub('entity.query.sql')->stubSetExecuteHandler(function () {
      UnitTestCaseWrapper::assertTrue(TestHelpers::queryIsSubsetOf($this, \Drupal::entityQuery('node')
        ->accessCheck()
        ->condition('status', 1)
        ->condition('type', 'article')
        ->sort('created', 'DESC')
        ->range(0, 2)));
      return ['2', '1'];
    });

    $result = TestHelpers::createClass(TestHelpersExampleController::class)->articlesList();
    $this->assertCount(2, $result['#items']);
    $this->assertEquals('A2 (02.01.2023 by Alice)', $result['#items'][0]->getText());
    $this->assertContains('node_list:article', $result['#cache']['tags']);
  }

}
