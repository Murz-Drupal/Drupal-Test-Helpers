<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Component\Utility\Random;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests Construct function.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class ProxyFunctionsTest extends UnitTestCase {

  /**
   * @covers ::getRandomGenerator
   * @covers Drupal\test_helpers\UnitTestCaseWrapper::getRandomGenerator
   */
  public function testGetRandomGenerator() {
    $this->assertInstanceOf(Random::class, TestHelpers::getRandomGenerator());
  }

  /**
   * @covers ::getContainerWithCacheTagsInvalidator
   * @covers \Drupal\test_helpers\UnitTestCaseWrapper::getContainerWithCacheTagsInvalidator
   */
  public function testGetContainerWithCacheTagsInvalidator() {
    $cacheTagsValidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $this->assertInstanceOf(ContainerInterface::class, TestHelpers::getContainerWithCacheTagsInvalidator($cacheTagsValidator));
  }

  /**
   * @covers ::createPartialMockWithCustomMethods
   * @covers Drupal\test_helpers\UnitTestCaseWrapper::createPartialMockWithCustomMethods
   */
  public function testCreatePartialMockWithCustomMethods() {
    $instance = TestHelpers::createPartialMockWithCustomMethods(TypedData::class, ['getValue'], ['addCacheTags']);
    $instance->method('getValue')->willReturn('foo');
    $instance->method('addCacheTags')->willReturn('bar');
    $this->assertEquals('foo', $instance->getValue());
    $this->assertEquals('bar', $instance->addCacheTags());
  }

}
