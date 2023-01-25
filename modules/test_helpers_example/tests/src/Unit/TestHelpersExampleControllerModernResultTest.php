<?php

namespace src\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\UnitTestHelpers;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests TestHelpersExampleController with Test Helpers API to check the result.
 *
 * @coversDefaultClass Drupal\test_helpers_example\Controller\TestHelpersExampleController
 * @group test_helpers_example
 */
class TestHelpersExampleControllerModernResultTest extends UnitTestCase {

  /**
   * @covers ::articlesList
   */
  public function testArticlesList() {
    UnitTestHelpers::createEntityStub(Node::class, ['type' => 'article', 'title' => 'Article 1', 'status' => '1'])->save();
    UnitTestHelpers::createEntityStub(Node::class, ['type' => 'article', 'title' => 'Article 2', 'status' => '1'])->save();
    UnitTestHelpers::createEntityStub(Node::class, ['type' => 'page', 'title' => 'Page 1', 'status' => '0'])->save();
    UnitTestHelpers::createEntityStub(Node::class, ['type' => 'article', 'title' => 'Article 3', 'status' => '0'])->save();
    UnitTestHelpers::createEntityStub(Node::class, ['type' => 'article', 'title' => 'Article 4', 'status' => '1'])->save();

    $result = (new TestHelpersExampleController())->articlesList();

    $this->assertCount(2, $result['#items']);
    $this->assertEquals('Article 4 (5)', $result['#items'][0]->getText());
    $this->assertEquals('Article 2 (2)', $result['#items'][1]->getText());
  }

}
