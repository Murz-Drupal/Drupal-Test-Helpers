<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * Tests EntityStorageStub main API functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\EntityStorageStub
 * @group test_helpers
 */
class EntityStorageStubApiTest extends UnitTestCase {

  /**
   * Tests creating an Entity Stub and storaga eactions.
   *
   * @covers ::__construct
   * @covers ::create
   */
  public function testEntityStorageStub() {
    // Creating mocked entities to test the results.
    $node1Values = [
      'type' => 'article',
      'title' => 'My cool article',
      'empty_field' => NULL,
      'body' => 'Very interesting article text.',
      'field_sign' => 'Alice',
      'field_tags' => [
        ['target_id' => 1],
        ['target_id' => 3],
      ],
    ];
    $node1Entity = UnitTestHelpers::createEntityStub(Node::class, $node1Values);

    // The `id` and `uuid` values should be NULL before saving, if not passed in
    // the `$values` array.
    $this->assertNull($node1Entity->id());
    $this->assertNull($node1Entity->uuid());

    $this->assertEquals($node1Values['type'], $node1Entity->bundle());
    $this->assertEquals($node1Values['title'], $node1Entity->title->value);
    $this->assertEquals($node1Values['field_tags'], $node1Entity->field_tags->getValue());
    $this->assertEquals($node1Values['field_tags'][1]['target_id'], $node1Entity->field_tags[1]->getValue()['target_id']);

    $this->assertFalse($node1Entity->title->isEmpty());
    $this->assertTrue($node1Entity->empty_field->isEmpty());

    require_once DRUPAL_ROOT . '/core/includes/common.inc';
    $this->assertEquals(SAVED_NEW, $node1Entity->save());
    $this->assertEquals(SAVED_UPDATED, $node1Entity->save());

    $this->assertEquals(1, $node1Entity->id());
    $this->assertEquals(1, preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $node1Entity->uuid()));

    $node1EntityId = $node1Entity->id();
    $node1Link = $node1Entity->toLink();
    $this->assertEquals($node1Values['title'], $node1Link->getText());
    $this->assertEquals("entity.node.canonical", $node1Link->getUrl()->getRouteName());
    $this->assertEquals("1", $node1Link->getUrl()->getRouteParameters()['node']);

    $node2Values = [
      'nid' => '42',
      'type' => 'page',
      'title' => 'My awesome page',
      'field_sign' => 'Alice',
      'body' => 'Pretty boring page text.',
    ];
    $node2Entity = UnitTestHelpers::createEntityStub(Node::class, $node2Values);

    $this->assertEquals($node2Values['title'], $node2Entity->title->value);

    $node2Entity->save();
    $node2EntityId = $node2Entity->id();

    $this->assertEquals(42, $node2Entity->id());

    $node3Values = [
      'type' => 'page',
      'title' => 'My second not so awesome page',
      'field_sign' => 'Bob',
      'body' => 'Very boring page text.',
    ];
    $node3Entity = UnitTestHelpers::createEntityStub(Node::class, $node3Values);
    $node3Entity->save();

    // The entity id should be auto-incremented over the max value.
    $this->assertEquals(43, $node3Entity->id());

    // Testing function EntityTypeManagerInterface::load().
    $node2LoadedById = \Drupal::service('entity_type.manager')->getStorage('node')->load($node2EntityId);
    $this->assertEquals($node2Values['body'], $node2LoadedById->body->value);

    // Testing function EntityTypeManagerInterface::loadMultiple().
    $nodeLoadedMultuple = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple([
      $node1EntityId,
      $node2EntityId,
    ]);
    $this->assertCount(2, $nodeLoadedMultuple);
    $nodeLoadedMultuple = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple();
    $this->assertCount(3, $nodeLoadedMultuple);

    // Testing function EntityTypeManagerInterface::loadByProperties().
    $entities = \Drupal::service('entity_type.manager')->getStorage('node')->loadByProperties(['field_sign' => 'Alice']);
    $this->assertCount(2, $entities);

    // Testing function EntityRepositoryInterface::loadEntityByUuid().
    $node2EntityUuid = $node2Entity->uuid();
    $node2EntityType = $node2Entity->getEntityTypeId();
    $node2LoadedByUuid = \Drupal::service('entity.repository')->loadEntityByUuid($node2EntityType, $node2EntityUuid);
    $this->assertEquals($node2Values['title'], $node2LoadedByUuid->title->value);

    // Testing function EntityRepositoryInterface::delete().
    $node2Entity->delete();
    $nodeLoadedMultuple = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple();
    $this->assertCount(2, $nodeLoadedMultuple);

  }

}
