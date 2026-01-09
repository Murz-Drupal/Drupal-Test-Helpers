<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\test_helpers\StubFactory\EntityStorageStubFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\node\Entity\Node;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests EntityStorageStub internal functionality.
 *
 * @covers EntityStorageStubFactory::__construct
 */
#[CoversClass(EntityStorageStubFactory::class)]
#[Group('test_helpers')]
class EntityStorageStubFunctionsTest extends UnitTestCase {

  /**
   * Tests the generateNewEntityId method of EntityStorageStubFactory.
   */
  public function testGenerateNewEntityId() {
    TestHelpers::saveEntity(Node::class, ['nid' => 42]);
    TestHelpers::saveEntity(Node::class, ['nid' => 12]);
    TestHelpers::saveEntity(Node::class);
    $entity = TestHelpers::saveEntity(Node::class);
    $this->assertSame('44', $entity->id());
  }

}
