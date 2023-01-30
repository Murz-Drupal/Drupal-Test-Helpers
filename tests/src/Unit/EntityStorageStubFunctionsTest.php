<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests EntityStorageStub internal functionality.
 *
 * @coversDefaultClass \Drupal\test_helpers\StubFactory\EntityStorageStubFactory
 * @group test_helpers
 */
class EntityStorageStubFunctionsTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testGenerateNewEntityId() {
    TestHelpers::createEntity(Node::class, ['nid' => 42])->save();
    TestHelpers::createEntity(Node::class, ['nid' => 12])->save();
    TestHelpers::createEntity(Node::class)->save();
    $entityStorageStub = \Drupal::service('entity_type.manager')->getStorage('node');
    $this->assertSame('44', $entityStorageStub->stubGetNewEntityId());
  }

}
