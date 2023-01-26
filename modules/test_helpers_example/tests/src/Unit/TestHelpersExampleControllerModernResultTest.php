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
    UnitTestHelpers::service('config.factory')->stubSetConfig('my_site', ['articles_to_display' => 1]);
    UnitTestHelpers::service('date.formatter')->stubSetFormat('medium', 'Medium', 'd.m.Y');
    UnitTestHelpers::saveEntityStub(Node::class, ['type' => 'article', 'title' => 'Article 1', 'status' => '1', 'created' => '1672574400']);
    UnitTestHelpers::saveEntityStub(Node::class, ['type' => 'article', 'title' => 'Article 2', 'status' => '1', 'created' => '1672660800']);
    UnitTestHelpers::saveEntityStub(Node::class, ['type' => 'page', 'title' => 'Page 1', 'status' => '0', 'created' => '1672747200']);
    UnitTestHelpers::saveEntityStub(Node::class, ['type' => 'article', 'title' => 'Article 3', 'status' => '0', 'created' => '1672833600']);

    $result = (new TestHelpersExampleController())->articlesList();

    $this->assertCount(1, $result['#items']);
    $this->assertEquals('Article 2 (02.01.2023)', $result['#items'][0]->getText());
  }

}
