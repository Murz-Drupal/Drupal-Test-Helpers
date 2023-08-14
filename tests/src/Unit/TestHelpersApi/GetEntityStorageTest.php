<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\taxonomy\Entity\Term;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Query helper functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class GetEntityStorageTest extends UnitTestCase {

  /**
   * @covers ::getEntityStorage
   * @covers \Drupal\test_helpers\StubFactory\EntityStorageStubFactory::create
   * @covers \Drupal\test_helpers\Stub\EntityTypeManagerStub::stubGetOrCreateStorage
   */
  public function testGetEntityStorage() {
    $storage1 = TestHelpers::getEntityStorage(Term::class, NULL, FALSE);
    $this->assertSame([], $storage1->loadMultiple());

    $storage2 = TestHelpers::getEntityStorage(Term::class, NULL, FALSE, [
      'mockMethods' => ['loadMultiple'],
    ]);
    $this->assertSame([], $storage2->loadMultiple());

    $storage3 = TestHelpers::getEntityStorage(Term::class, NULL, TRUE, [
      'mockMethods' => ['loadMultiple'],
    ]);
    $this->assertSame(NULL, $storage3->loadMultiple());

    $storage4 = TestHelpers::getEntityStorage(Term::class, NULL, TRUE, [
      'addMethods' => ['newMethod1', 'newMethod2'],
    ]);
    $storage4->method('newMethod2')->willReturn('foo');
    $this->assertSame(NULL, $storage4->newMethod1());
    $this->assertSame('foo', $storage4->newMethod2());
  }

}
