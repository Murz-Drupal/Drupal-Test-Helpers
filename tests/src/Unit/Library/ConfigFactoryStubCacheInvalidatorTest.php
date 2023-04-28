<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\test_helpers\lib\ConfigFactoryStubCacheInvalidator;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\lib\ConfigFactoryStubCacheInvalidator
 * @group test_helpers
 */
class ConfigFactoryStubCacheInvalidatorTest extends UnitTestCase {

  /**
   * @covers ::invalidateTags
   */
  public function testInvalidateTags() {
    $configFactory = TestHelpers::service('config.factory', $this->createMock(ConfigFactoryInterface::class));
    $configFactory->method('reset')->willReturnCallback(function ($tag) {
      $this->assertEquals('bar', $tag);
    });
    $invalidator = new ConfigFactoryStubCacheInvalidator();
    $invalidator->invalidateTags(['foo', 'config:bar:baz']);
  }

}
