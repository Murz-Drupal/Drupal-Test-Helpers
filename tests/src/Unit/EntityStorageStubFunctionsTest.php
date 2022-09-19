<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\node\Entity\Node;
use Drupal\test_helpers\EntityStorageStub;
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
  public function setUp() {
    parent::setUp();
    $this->entityStorageStub = new EntityStorageStub();
  }

  /**
   * @covers ::__construct
   * @covers ::generateNewEntityId
   */
  public function testGenerateNewEntityId() {
    $this->entityStorageStub->createEntityStub(Node::class, ['nid' => 42])->save();
    $this->entityStorageStub->createEntityStub(Node::class, ['nid' => 12])->save();
    $this->entityStorageStub->createEntityStub(Node::class)->save();
    $this->assertSame('44', $this->entityStorageStub->generateNewEntityId('node'));
  }

}
