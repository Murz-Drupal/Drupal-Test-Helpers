<?php

namespace Drupal\Tests\test_helpers_example\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\test_helpers_example\Controller\TestHelpersExampleController
 * @group test_helpers_example
 */
class TestHelpersExampleControllerKernelClassicTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'test_helpers_example'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Tests articlesList() function.
   */
  public function testArticlesList() {
    \Drupal::service('config.factory')->getEditable('test_helpers_example.settings')
      ->set('articles_to_display', 1)->save();
    DateFormat::load('medium')->setPattern('d.m.Y')->save();
    $user1 = User::create(['name' => 'Alice']);
    $user1->save();
    NodeType::create(['type' => 'article'])->save();
    // Putting coding standards ignore flag to suppress warnings until the
    // https://www.drupal.org/project/coder/issues/3185082 is fixed.
    // @codingStandardsIgnoreStart
    Node::create(['type' => 'article', 'title' => 'A1', 'status' => 1, 'uid' => $user1->id(), 'created' => 1672574400])->save();
    Node::create(['type' => 'article', 'title' => 'A2', 'status' => 1, 'uid' => $user1->id(), 'created' => 1672660800])->save();
    Node::create(['type' => 'page',    'title' => 'P1', 'status' => 1, 'uid' => $user1->id(), 'created' => 1672747200])->save();
    Node::create(['type' => 'article', 'title' => 'A3', 'status' => 0, 'uid' => $user1->id(), 'created' => 1672833600])->save();
    // @codingStandardsIgnoreEnd

    $controller = new TestHelpersExampleController(
      $this->container->get('config.factory'),
      $this->container->get('entity_type.manager'),
      $this->container->get('date.formatter'),
    );

    $result = $controller->articlesList();
    $this->assertCount(1, $result['#items']);
    $this->assertEquals('A2 (02.01.2023 by Alice)', $result['#items'][0]->getText());
    $this->assertContains('node:type:article', $result['#cache']['tags']);
  }

}
