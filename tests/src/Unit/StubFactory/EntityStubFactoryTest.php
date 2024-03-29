<?php

namespace Drupal\Tests\test_helpers\Unit\Stubs;

use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigStorage;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\test_helpers\StubFactory\EntityStorageStubFactory;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;

/**
 * Tests LanguageManagerStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\StubFactory\EntityStubFactory
 * @group test_helpers
 */
class EntityStubFactoryTest extends UnitTestCase {

  /**
   * A condition.
   *
   * @var \Drupal\Core\Entity\Query\ConditionInterface
   */
  protected ConditionInterface $condition;

  /**
   * @covers ::create
   */
  public function testUserAndNodeTypes() {
    TestHelpers::saveEntity(NodeType::class, [
      'type' => 'article',
      'name' => 'Article',
    ]);
    $user1notSaved = TestHelpers::createEntity(User::class, ['name' => 'Bob']);
    TestHelpers::saveEntity(User::class, ['name' => 'Alice']);
    $user1notSaved->save();

    $node1 = TestHelpers::createEntity(Node::class, [
      'uid' => '2',
      'type' => 'article',
      'title' => 'Node 1',
      'field_custom_field1' => 'Value 1',
      'field_custom_field2' => NULL,
    ]);
    $node1->title = 'Node 1 overriden';
    $node1->field_custom_field1 = 'Overriden value 1';
    $node1->field_custom_field2 = 'Overriden value 2';
    $this->assertEquals('Node 1 overriden', $node1->label());
    $this->assertEquals('Node 1 overriden', $node1->title->value);
    $this->assertEquals('Overriden value 1', $node1->field_custom_field1->value);
    $this->assertEquals('Overriden value 2', $node1->field_custom_field2->value);
    $this->assertEquals('Bob', $node1->uid->entity->label());
    $this->assertEquals('Bob', $node1->uid->entity->label());
    $node1->save();

    $node2 = TestHelpers::saveEntity(Node::class, [
      'nid' => '42',
      'title' => 'Node 2',
    ]);

    $node3 = TestHelpers::saveEntity(Node::class, ['title' => 'Node 3']);

    $node1Loaded = \Drupal::service('entity_type.manager')->getStorage('node')->load(1);
    $this->assertEquals($node1Loaded->label(), $node1->label());
    $this->assertEquals('Overriden value 2', $node1Loaded->field_custom_field2->value);
    $this->assertEquals('Article', $node1Loaded->type->entity->label());

    $node2Loaded = \Drupal::service('entity_type.manager')->getStorage('node')->load(42);
    $this->assertEquals($node2Loaded->label(), $node2->label());

    $nodes = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple();
    $this->assertEquals($nodes[42]->label(), $node2->label());
    $this->assertEquals($nodes[43]->label(), $node3->label());
  }

  /**
   * @covers ::create
   */
  public function testTermType() {
    $entity1 = TestHelpers::createEntity(Term::class, [
      'uid' => 1,
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

    $entity3 = TestHelpers::saveEntity(Term::class, [
      'name' => 'Entity 3',
      'parent' => 1,
    ]);

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
   * Tests Media entity.
   */
  public function testMediaEntity() {
    // @todo Make this work with pre-post-save functions.
    TestHelpers::saveEntity(
      MediaType::class,
      ['id' => 'type1'],
      NULL,
      ['skipPrePostSave' => TRUE]
    );
    $entity1 = TestHelpers::saveEntity(
      Media::class,
      ['name' => 'Foo'],
      NULL,
      ['skipPrePostSave' => TRUE]
    );
    $entity2 = TestHelpers::saveEntity(
      Media::class,
      ['bundle' => 'type1', 'name' => 'Foo'],
      NULL,
      ['skipPrePostSave' => TRUE]
    );
    $this->assertEquals('Foo', $entity1->name->value);
    $this->assertEquals('type1', $entity2->bundle->entity->id());
  }

  /**
   * Tests MenuLinkContent entities.
   */
  public function testMenuLinkContentEntities() {
    TestHelpers::service('plugin.manager.menu.link');
    $e1 = TestHelpers::createEntity('menu_link_content', [
      'title' => 'Menu Item 1',
      'bundle' => 'bundle1',
      'menu_name' => 'menu1',
      'link' => [
        'uri' => 'route:<nolink>',
      ],
    ]);
    $e1->save();
    $e2 = TestHelpers::saveEntity(MenuLinkContent::class, [
      'title' => 'Menu Item 2',
      'menu_name' => 'menu2',
      'link' => [
        'title' => 'External link',
        'uri' => 'http://example.com/page1',
      ],
    ]);
    $storage = \Drupal::service('entity_type.manager')->getStorage('menu_link_content');
    $entitiesIds = $storage->getQuery()->accessCheck(FALSE)->execute();
    $this->assertEquals([1 => '1', 2 => '2'], $entitiesIds);
    $entities = $storage->loadMultiple();
    $this->assertCount(2, $entities);
    $this->assertEquals($e1->title->value, $entities[1]->title->value);
    $this->assertEquals($e2->link->uri, $entities[2]->link->uri);
  }

  /**
   * Tests revisions API.
   */
  public function testRevisions() {
    TestHelpers::saveEntity(User::class);
    $node1 = TestHelpers::saveEntity(Node::class, ['title' => 'Entity 1 Revision 1']);
    $this->assertEquals(1, $node1->getRevisionId());

    $node1->title = 'Entity 1 Revision 1 still';
    $node1->status = 1;
    $node1->save();
    $this->assertEquals(1, $node1->getRevisionId());

    $node1Loaded = \Drupal::service('entity_type.manager')->getStorage('node')->load(1);
    $this->assertEquals(1, $node1Loaded->getLoadedRevisionId());

    $node1Loaded->title = 'Entity 1 Revision 2';
    $node1Loaded->setNewRevision(TRUE);
    $node1Loaded->save();
    $this->assertEquals(2, $node1Loaded->getRevisionId());
    $this->assertEquals(2, $node1Loaded->getLoadedRevisionId());

    $node1->title = 'Entity 1 Revision 2 still';
    $node1->setNewRevision(FALSE);
    $node1->save();
    $this->assertEquals(1, $node1->getRevisionId());

    $node2 = TestHelpers::saveEntity(Node::class, ['title' => 'Entity 2 Revision 2']);
    $this->assertEquals(3, $node2->getRevisionId());
    $node2->setNewRevision(TRUE);
    $node2->save();
    $this->assertEquals(4, $node2->getRevisionId());

    $node1->title = 'Entity 1 Revision 3';
    $node1->setNewRevision(TRUE);
    $node1->status = 0;
    $node1->save();
    $this->assertEquals(5, $node1->getRevisionId());

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $nodeStorage */
    $nodeStorage = \Drupal::service('entity_type.manager')->getStorage('node');
    $nodeLoaded = $nodeStorage->load($node1->id());
    // The revision id should be 2, because the last revision is not published.
    $this->assertEquals(2, $nodeLoaded->getRevisionId());

    $nodeLoaded = $nodeStorage->loadRevision(2);
    $this->assertEquals(2, $nodeLoaded->getRevisionId());
    $nodeLoaded = $nodeStorage->loadRevision(1);
    $this->assertEquals(1, $nodeLoaded->getRevisionId());

    $term1 = TestHelpers::createEntity(Term::class, ['name' => 'Term 1 Revision 1']);
    $term2 = TestHelpers::saveEntity(Term::class, ['name' => 'Term 1 Revision 1']);
    $term1->save();
    $this->assertEquals(2, $term1->getRevisionId());
    $this->assertEquals(1, $term2->getRevisionId());
  }

  /**
   * Tests revisions API.
   */
  public function testFullyMockedEntity() {
    $fieldStorageConfig = $this->createMock(FieldStorageConfig::class);
    $fieldStorageConfig->method('getBundles')->willReturn(['foo', 'bar']);
    $fieldStorageConfigStorage = $this->createMock(FieldStorageConfigStorage::class);
    $fieldStorageConfigStorage->method('load')->willReturn($fieldStorageConfig);
    TestHelpers::getEntityStorage(FieldStorageConfig::class, $fieldStorageConfigStorage);

    $storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    $this->assertEquals(['foo', 'bar'], $storage->load(123)->getBundles());
  }

}
