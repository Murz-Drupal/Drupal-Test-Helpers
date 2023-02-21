<?php

namespace Drupal\Tests\test_helpers\Unit\Stubs;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\StubFactory\EntityStorageStubFactory;
use Drupal\test_helpers\StubFactory\FieldItemListStubFactory;
use Drupal\test_helpers\TestHelpers;
use Drupal\user\Entity\User;

/**
 * Tests LanguageManagerStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\StubFactory\EntityStorageStubFactory
 * @group test_helpers
 */
class EntityStorageStubFactoryTest extends UnitTestCase {

  /**
   * @covers ::create
   */
  public function testCreate() {
    $entity1 = TestHelpers::createEntity(Term::class, [
      'name' => 'Entity 1',
      'parent' => NULL,
    ]);
    $storage = EntityStorageStubFactory::create(
      Term::class,
    );
    $storage->save($entity1);

    $entity2 = TestHelpers::createEntity(Term::class, [
      'name' => 'Entity 2',
      'parent' => ['target_id' => 1],
    ]);
    $entity2->save();

    $entity3 = TestHelpers::createEntity(Term::class, [
      'name' => 'Entity 3',
      'parent' => ['target_id' => 1],
    ]);
    $storage->save($entity3);

    $result = $storage->loadMultiple();
    $this->assertEquals($entity1->id(), $result[1]->id());
    $this->assertEquals($entity2->id(), $result[2]->id());

    \Drupal::service('entity.query.sql')->stubSetExecuteHandler(function () {
      return $this->condition->conditions()[0]['value'];
    });

    $storageSpecificFuncResult = $storage->loadAllParents($entity3->id());
    end($storageSpecificFuncResult);
    $this->assertEquals($entity1->id(), current($storageSpecificFuncResult)->id());
    reset($storageSpecificFuncResult);
    $this->assertEquals($entity3->id(), current($storageSpecificFuncResult)->id());
    $this->assertArrayNotHasKey(2, $storageSpecificFuncResult);

  }

  /**
   * Tests entity reference base field type.
   */
  public function testEntityReferenceField() {
    TestHelpers::saveEntity(User::class, [
      'name' => 'Foo',
    ]);
    TestHelpers::saveEntity(User::class, [
      'name' => 'Bar',
    ]);

    $node1 = TestHelpers::saveEntity(Node::class, [
      'title' => 'Entity reference test 1',
      'uid' => 2,
    ]);
    $this->assertEquals('Bar', $node1->uid->entity->name->value);

    $entityReferenceUserFieldDefinition = FieldItemListStubFactory::createFieldItemDefinitionStub('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', ['target_type' => 'user']);
    $entityReferenceNodeFieldDefinition = FieldItemListStubFactory::createFieldItemDefinitionStub('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', ['target_type' => 'node']);

    $node2 = TestHelpers::saveEntity(Node::class, [
      'title' => 'Entity reference test 2',
      'uid' => 1,
      'field_user_reference' => 2,
      'field_node_reference' => 1,
    ],
    [
      'definitions' => [
        'field_user_reference' => $entityReferenceUserFieldDefinition,
        'field_node_reference' => $entityReferenceNodeFieldDefinition,
      ],
    ]);

    $this->assertEquals('Foo', $node2->uid->entity->label());
    $this->assertEquals('Bar', $node2->field_user_reference->entity->label());
    $this->assertEquals('Entity reference test 1', $node2->field_node_reference->entity->label());
  }

}
