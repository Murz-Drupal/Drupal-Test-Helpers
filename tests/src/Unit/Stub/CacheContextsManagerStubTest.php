<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\Stub\CacheContextsManagerStub;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests CacheContextsManagerStub class.
 */
#[CoversClass(CacheContextsManagerStub::class)]
#[Group('test_helpers')]
#[CoversMethod(CacheContextsManagerStub::class, '__construct')]
#[CoversMethod(CacheContextsManagerStub::class, 'stubAddContexts')]
class CacheContextsManagerStubTest extends UnitTestCase {

  /**
   * Tests the CacheContextsManagerStub methods.
   */
  public function testStub() {
    $cacheContextsManagerStub = TestHelpers::service('cache_contexts_manager');

    $this->assertInstanceOf(CacheContextsManagerStub::class, $cacheContextsManagerStub);

    // Should return TRUE for any context name by default.
    $this->assertTrue($cacheContextsManagerStub->assertValidTokens(['some_context1']));

    $cacheContextsManagerStub->stubAddContexts('custom_context1:group1');
    $cacheContextsManagerStub->stubAddContexts(['custom_context2']);

    $this->assertTrue($cacheContextsManagerStub->assertValidTokens(['custom_context1:group1']));
    $this->assertTrue($cacheContextsManagerStub->assertValidTokens(['custom_context2:group2:subgroup1']));
    $this->assertTrue($cacheContextsManagerStub->assertValidTokens(['custom_context2:group2:subgroup2']));

    // Should return TRUE for non-defined context names, if some become defined.
    $this->assertFalse($cacheContextsManagerStub->assertValidTokens(['custom_context1']));
    $this->assertFalse($cacheContextsManagerStub->assertValidTokens(['custom_context3']));

    $cacheContextsManagerStub->stubSetContexts(['custom_context2']);
    // Should return FALSE because the context should be overridden as missing.
    $this->assertFalse($cacheContextsManagerStub->assertValidTokens(['custom_context1:group1']));

  }

}
