<?php

namespace Drupal\Tests\test_helpers\Unit\Stubs;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests LanguageManagerStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\StubFactory\EntityStubFactory
 * @group test_helpers
 */
class EntityStubFactoryTranslationTest extends UnitTestCase {

  /**
   * Tests translation feature for entity stubs.
   */
  public function testTranslation() {
    TestHelpers::service('language_manager')->stubAddLanguage('fr');
    TestHelpers::service('language_manager')->stubAddLanguages(['de']);
    $node = TestHelpers::createEntity(
      Node::class,
      [
        'title' => 'Title in en language',
        'field_translatable_field' => 'Value in en language',
        'field_untranslatable_field' => 'Value in en language',
      ],
      [
        'fr' => [
          'title' => 'Title in fr language',
          'field_translatable_field' => 'Value in fr language',
          'field_untranslatable_field' => 'Value in fr language',
        ],
      ],
      [
        'fields' => [
          'field_translatable_field' => [
            'type' => 'string',
            'translatable' => TRUE,
          ],
        ],
      ]
    );

    $node->addTranslation('de', [
      'title' => 'Title in de language',
      'field_translatable_field' => 'Value in de language',
      'field_untranslatable_field' => 'Value in de language',
    ]);
    $node->save();

    $trFr = $node->getTranslation('fr');
    $trDe = TestHelpers::service('entity.repository')->getTranslationFromContext($node, 'de');

    $this->assertEquals($node->id(), $trDe->id());
    $this->assertEquals($node->getRevisionId(), $trDe->getRevisionId());

    $this->assertEquals('Title in en language', $node->title->value);
    $this->assertEquals('Title in fr language', $trFr->title->value);
    $this->assertEquals('Title in de language', $trDe->title->value);

    $this->assertEquals('Value in en language', $node->field_translatable_field->value);
    $this->assertEquals('Value in fr language', $trFr->field_translatable_field->value);
    $this->assertEquals('Value in de language', $trDe->field_translatable_field->value);

    $this->assertEquals('Value in en language', $node->field_untranslatable_field->value);
    $this->assertEquals('Value in en language', $trFr->field_untranslatable_field->value);
    $this->assertEquals('Value in en language', $trDe->field_untranslatable_field->value);
  }

}
