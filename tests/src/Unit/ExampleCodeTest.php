<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\EntityStubFactory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests EntityStorageStub main API functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\EntityStorageStub
 * @group test_helpers
 */
class ExampleCodeTest extends UnitTestCase {

  /**
   * Tests the module Example code.
   */
  public function testExampleCode() {
    $entityStubFactory = new EntityStubFactory();

    $node1Values = [
      'type' => 'article',
      'title' => 'My cool article',
      'body' => 'Very interesting article text.',
      'field_tags' => [
        ['target_id' => 1],
        ['target_id' => 3],
      ],
    ];
    $node1Entity = $entityStubFactory->create(Node::class, $node1Values);
    $node1Entity->save();

    $node1EntityId = $node1Entity->id();
    $node1EntityUuid = $node1Entity->uuid();
    $node1EntityType = $node1Entity->getEntityTypeId();

    $node1LoadedById = \Drupal::service('entity_type.manager')->getStorage('node')->load($node1EntityId);

    $node1LoadedByUuid = \Drupal::service('entity.repository')->loadEntityByUuid($node1EntityType, $node1EntityUuid);

    $this->assertEquals(1, $node1LoadedById->id());
    $this->assertEquals(1, preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $node1LoadedByUuid->uuid()));
  }

}
