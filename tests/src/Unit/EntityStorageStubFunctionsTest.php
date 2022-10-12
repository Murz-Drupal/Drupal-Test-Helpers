<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\UnitTestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests EntityStorageStub internal functionality.
 *
 * @coversDefaultClass \Drupal\test_helpers\EntityStorageStub
 * @group test_helpers
 */
class EntityStorageStubFunctionsTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::generateNewEntityId
   */
  public function testGenerateNewEntityId() {
    UnitTestHelpers::createEntityStub(Node::class, ['nid' => 42])->save();
    UnitTestHelpers::createEntityStub(Node::class, ['nid' => 12])->save();
    UnitTestHelpers::createEntityStub(Node::class)->save();
    $entityStorageStub = \Drupal::service('entity_type.manager')->getStorage('node');
    $this->assertSame('44', $entityStorageStub->stubGetNewEntityId());
  }

}
