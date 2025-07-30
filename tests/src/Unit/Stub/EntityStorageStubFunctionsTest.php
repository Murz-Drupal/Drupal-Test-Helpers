<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\node\Entity\Node;
use Drupal\test_helpers\TestHelpers;

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
    TestHelpers::saveEntity(Node::class, ['nid' => 42]);
    TestHelpers::saveEntity(Node::class, ['nid' => 12]);
    TestHelpers::saveEntity(Node::class);
    $entity = TestHelpers::saveEntity(Node::class);
    $this->assertSame('44', $entity->id());
  }

}
