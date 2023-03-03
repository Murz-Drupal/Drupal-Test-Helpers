<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\CacheContextsManagerStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\CacheContextsManagerStub
 * @group test_helpers
 */
class CacheContextsManagerStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::stubAddContexts
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
    // Should return FALSE because the context should be overriten as missing.
    $this->assertFalse($cacheContextsManagerStub->assertValidTokens(['custom_context1:group1']));

  }

}
