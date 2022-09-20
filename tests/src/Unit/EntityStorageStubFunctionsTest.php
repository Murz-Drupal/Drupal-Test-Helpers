<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\EntityStubFactory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests EntityStorageStub internal functionality.
 *
 * @coversDefaultClass \Drupal\test_helpers\EntityStorageStub
 * @group test_helpers
 */
class EntityStorageStubFunctionsTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityStubFactory = new EntityStubFactory();
    $this->entityStorageStubFactory = new EntityStubFactory();
  }

  /**
   * @covers ::__construct
   * @covers ::generateNewEntityId
   */
  public function testGenerateNewEntityId() {
    $this->entityStubFactory->create(Node::class, ['nid' => 42])->save();
    $this->entityStubFactory->create(Node::class, ['nid' => 12])->save();
    $this->entityStubFactory->create(Node::class)->save();
    $entityStorageStub = \Drupal::service('entity_type.manager')->getStorage('node');
    $this->assertSame('44', $entityStorageStub->stubGetNewEntityId());
  }

}
