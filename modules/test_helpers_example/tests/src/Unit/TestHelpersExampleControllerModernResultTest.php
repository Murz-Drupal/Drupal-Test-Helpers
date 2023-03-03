<?php

namespace Drupal\Tests\test_helpers_example\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\TestHelpers;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;

/**
 * Tests TestHelpersExampleController with Test Helpers API to check the result.
 *
 * @coversDefaultClass \Drupal\test_helpers_example\Controller\TestHelpersExampleController
 * @group test_helpers_example
 */
class TestHelpersExampleControllerModernResultTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::create
   * @covers ::articlesList
   */
  public function testArticlesList() {
    TestHelpers::service('config.factory')->stubSetConfig('test_helpers_example.settings', ['articles_to_display' => 1]);
    TestHelpers::service('date.formatter')->stubSetFormat('medium', 'Medium', 'd.m.Y');
    TestHelpers::saveEntity(User::class, ['name' => 'Alice']);
    // Putting coding standards ignore flag to suppress warnings until the
    // https://www.drupal.org/project/coder/issues/3185082 is fixed.
    // @codingStandardsIgnoreStart
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A1', 'status' => 1, 'uid' => 1, 'created' => 1672574400]);
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A2', 'status' => 1, 'uid' => 1, 'created' => 1672660800]);
    TestHelpers::saveEntity(Node::class, ['type' => 'page',    'title' => 'P1', 'status' => 1, 'uid' => 1, 'created' => 1672747200]);
    TestHelpers::saveEntity(Node::class, ['type' => 'article', 'title' => 'A3', 'status' => 0, 'uid' => 1, 'created' => 1672833600]);
    // @codingStandardsIgnoreEnd

    $result = TestHelpers::createClass(TestHelpersExampleController::class)->articlesList();
    $this->assertCount(1, $result['#items']);
    $this->assertEquals('A2 (02.01.2023 by Alice)', $result['#items'][0]->getText());
    $this->assertContains('node:type:article', $result['#cache']['tags']);
  }

}
