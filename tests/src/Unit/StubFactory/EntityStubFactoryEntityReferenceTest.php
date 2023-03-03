<?php

namespace Drupal\Tests\test_helpers\Unit\Stubs;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\node\Entity\Node;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\StubFactory\FieldItemListStubFactory;
use Drupal\test_helpers\TestHelpers;
use Drupal\user\Entity\User;

/**
 * Tests LanguageManagerStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\StubFactory\EntityStubFactory
 * @group test_helpers
 */
class EntityStubFactoryEntityReferenceTest extends UnitTestCase {

  /**
   * Tests entity reference base field type.
   */
  public function testEntityReferenceField() {
    TestHelpers::saveEntity(User::class, [
      'name' => 'Alice',
    ]);
    TestHelpers::saveEntity(User::class, [
      'name' => 'Bob',
    ]);

    $node1 = TestHelpers::saveEntity(Node::class, [
      'title' => 'Entity reference test 1',
      'uid' => 2,
    ]);
    $this->assertEquals('Bob', $node1->uid->entity->name->value);

    $entityReferenceUserFieldDefinition = FieldItemListStubFactory::createFieldItemDefinitionStub(EntityReferenceItem::class, ['target_type' => 'user']);
    $entityReferenceNodeFieldDefinition = FieldItemListStubFactory::createFieldItemDefinitionStub('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', ['target_type' => 'node']);

    $node2 = TestHelpers::saveEntity(
      Node::class,
      [
        'title' => 'Entity reference test 2',
        'uid' => 1,
        'field_user_reference1' => 2,
        'field_node_reference1' => 1,
        'field_node_reference2' => 1,
      ],
      NULL,
      [
        'fields' => [
          'field_node_reference1' =>
          [
            '#type' => 'entity_reference',
            '#settings' => ['target_type' => 'node'],
          ],
          'field_user_reference1' => $entityReferenceUserFieldDefinition,
          'field_node_reference2' => $entityReferenceNodeFieldDefinition,
        ],
      ]
    );

    $this->assertEquals('Alice', $node2->uid->entity->label());
    $this->assertEquals('Bob', $node2->field_user_reference1->entity->label());
    $this->assertEquals('Entity reference test 1', $node2->field_node_reference1->entity->label());
    $this->assertEquals('Entity reference test 1', $node2->field_node_reference2->entity->label());
  }

}
