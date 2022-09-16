<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\EntityStorageStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * Tests EntityStorageStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\EntityStorageStub
 * @group test_helpers
 */
class EntityStorageStubTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    $this->entityStorageStub = new EntityStorageStub();
    $this->unitTestHelpers = new UnitTestHelpers();
  }

  /**
   * Tests creating an Entity Stub and storaga eactions.
   *
   * @covers ::__construct
   * @covers ::createEntityStub
   */
  public function testEntityStorageStub() {
    $node1Values = [
      'type' => 'article',
      'title' => 'My cool article',
      'body' => 'Very interesting article text.',
      'field_tags' => [
        ['target_id' => 1],
        ['target_id' => 3],
      ],
    ];
    $node1Entity = $this->entityStorageStub->createEntityStub(Node::class, $node1Values);
    $this->assertNull($node1Entity->id());
    $this->assertNull($node1Entity->uuid());
    $this->assertEquals($node1Values['type'], $node1Entity->bundle());
    $this->assertEquals($node1Values['title'], $node1Entity->title->value);
    $this->assertEquals($node1Values['field_tags'], $node1Entity->field_tags->getValue());
    $this->assertEquals($node1Values['field_tags'][1]['target_id'], $node1Entity->field_tags[1]->getValue()['target_id']);

    $node1Entity->save();
    $this->assertEquals(1, $node1Entity->id());
    $this->assertEquals(1, preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $node1Entity->uuid()));

    $node2Values = [
      'type' => 'page',
      'title' => 'My awesome page',
      'body' => 'Pretty boring page text.',
    ];
    $node2Entity = $this->entityStorageStub->createEntityStub(Node::class, $node2Values);

    $this->assertEquals($node2Values['title'], $node2Entity->title->value);

    $node2Entity->save();
    $this->assertEquals(2, $node2Entity->id());

    $node2EntityId = $node2Entity->id();
    $node2LoadedById = \Drupal::service('entity_type.manager')->getStorage('node')->load($node2EntityId);
    $this->assertEquals($node2Values['body'], $node2LoadedById->body->value);

    $node2EntityUuid = $node2Entity->uuid();
    $node2EntityType = $node2Entity->getEntityTypeId();
    $node2LoadedByUuid = \Drupal::service('entity.repository')->loadEntityByUuid($node2EntityType, $node2EntityUuid);
    $this->assertEquals($node2Values['title'], $node2LoadedByUuid->title->value);
  }

}
