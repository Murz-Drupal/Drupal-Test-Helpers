<?php

namespace Drupal\Tests\test_helpers_example\Unit;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\test_helpers_example\ArticlesManagerService
 * @group test_helpers_example
 */
class ArticlesServiceTest extends UnitTestCase {

  /**
   * Tests Test Helpers API, related to entities.
   */
  public function testEntities() {
    // Putting coding standards ignore flag to suppress warnings until the
    // https://www.drupal.org/project/coder/issues/3185082 is fixed.
    // @codingStandardsIgnoreStart
    TestHelpers::service('language_manager')->stubAddLanguages(['fr', 'de']);
    $user1 = TestHelpers::saveEntity(User::class, ['name' => 'Alice']);
    $user2 = TestHelpers::saveEntity(User::class, ['name' => 'Bob']);
    $vocabulary = TestHelpers::saveEntity(Vocabulary::class, ['vid' => 'categories', 'name' => 'Category']);
    $term1 = TestHelpers::saveEntity(Term::class, ['name' => 'Articles', 'vid' => $vocabulary->id()]);
    $term2 = TestHelpers::saveEntity(Term::class, ['name' => 'Boring articles', 'vid' => $vocabulary->id(), 'parent' => $term1->id()]);

    TestHelpers::saveEntity(Node::class, [
      'title' => 'A boring story',
      'uid' => $user1->id(),
      'field_category' => $term2->id(),
      'field_synopsis' => 'A pretty boring story.',
    ], NULL, [
        'fields' => [
          'uid' => ['translatable' => FALSE],
          'field_category' => ['#type' => 'entity_reference', '#settings' => ['target_type' => 'taxonomy_term']],
          'field_synopsis' => ['#settings' => ['translatable' => TRUE]],
        ],
      ]);
    TestHelpers::saveEntity(Node::class, [
      'uid' => $user2->id(),
      'title' => 'A cool story',
      'field_synopsis' => 'A story about cool things.',
      'field_category' => $term1->id(),
    ], [
        'fr' => ['title' => 'Une histoire sympa', 'field_synopsis' => 'Une histoire de choses sympas.'],
        'de' => ['title' => 'Eine coole Geschichte', 'field_synopsis' => 'Eine Geschichte über coole Dinge.', 'field_category' => 'Boring articles'],
      ]);

    $service = TestHelpers::initService('test_helpers_example.articles_manager');

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($service->getTranslatedArticlesList('en'), [
      ['title' => 'A boring story', 'author' => 'Alice', 'term' => 'Category: Boring articles'],
      ['title' => 'A cool story', 'author' => 'Bob', 'term' => 'Category: Articles'],
    ]));
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($service->getTranslatedArticlesList('fr'), [
      ['title' => 'Une histoire sympa', 'author' => 'Bob', 'term' => 'Category: Articles'],
    ]));
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($service->getTranslatedArticlesList('de'), [
      ['title' => 'Eine coole Geschichte', 'author' => 'Bob', 'term' => 'Category: Articles'],
    ]));
    // @codingStandardsIgnoreEnd
  }

}
