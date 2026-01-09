<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Drupal\test_helpers\lib\ConfigFactoryStubCacheInvalidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests ConfigFactoryStubCacheInvalidator class.
 *
 * @covers ConfigFactoryStubCacheInvalidator::invalidateTags
 */
#[CoversClass(ConfigFactoryStubCacheInvalidator::class)]
#[Group('test_helpers')]
class ConfigFactoryStubCacheInvalidatorTest extends UnitTestCase {

  /**
   * Tests the invalidateTags() method.
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
