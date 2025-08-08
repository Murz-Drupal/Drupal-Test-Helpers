<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\taxonomy\Entity\Term;
use Drupal\test_helpers\StubFactory\EntityStorageStubFactory;
use Drupal\test_helpers\Stub\EntityTypeManagerStub;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Query helper functions.
 */
#[CoversClass(TestHelpers::class)]
#[CoversClass(EntityStorageStubFactory::class)]
#[CoversClass(EntityTypeManagerStub::class)]
#[Group('test_helpers')]
#[CoversMethod(TestHelpers::class, 'getEntityStorage')]
#[CoversMethod(EntityStorageStubFactory::class, 'create')]
#[CoversMethod(EntityTypeManagerStub::class, 'stubGetOrCreateStorage')]
class GetEntityStorageTest extends UnitTestCase {

  /**
   * Tests the getEntityStorage() function.
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
    $this->assertNull($storage3->loadMultiple());

    $storage4 = TestHelpers::getEntityStorage(Term::class, NULL, TRUE, [
      'addMethods' => ['newMethod1', 'newMethod2'],
    ]);
    $storage4->method('newMethod2')->willReturn('foo');
    $this->assertNull($storage4->newMethod1());
    $this->assertSame('foo', $storage4->newMethod2());
  }

}
