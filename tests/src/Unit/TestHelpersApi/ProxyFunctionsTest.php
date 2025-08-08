<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Component\Utility\Random;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\test_helpers\UnitTestCaseWrapper;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Construct function.
 */
#[CoversClass(TestHelpers::class)]
#[CoversClass(UnitTestCaseWrapper::class)]
#[Group('test_helpers')]
#[CoversMethod(TestHelpers::class, 'getRandomGenerator')]
#[CoversMethod(UnitTestCaseWrapper::class, 'getRandomGenerator')]
#[CoversMethod(TestHelpers::class, 'getContainerWithCacheTagsInvalidator')]
#[CoversMethod(UnitTestCaseWrapper::class, 'getContainerWithCacheTagsInvalidator')]
#[CoversMethod(TestHelpers::class, 'createPartialMockWithCustomMethods')]
#[CoversMethod(UnitTestCaseWrapper::class, 'createPartialMockWithCustomMethods')]
class ProxyFunctionsTest extends UnitTestCase {

  /**
   * Tests the getRandomGenerator function.
   */
  public function testGetRandomGenerator() {
    $this->assertInstanceOf(Random::class, TestHelpers::getRandomGenerator());

  }

  /**
   * Tests the getContainerWithCacheTagsInvalidator function.
   */
  public function testGetContainerWithCacheTagsInvalidator() {
    $cacheTagsValidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $this->assertInstanceOf(ContainerInterface::class, TestHelpers::getContainerWithCacheTagsInvalidator($cacheTagsValidator));

  }

  /**
   * Tests the createPartialMockWithCustomMethods function.
   */
  public function testCreatePartialMockWithCustomMethods() {
    $instance = TestHelpers::createPartialMockWithCustomMethods(TypedData::class, ['getValue'], ['addCacheTags']);
    $instance->method('getValue')->willReturn('foo');
    $instance->method('addCacheTags')->willReturn('bar');
    $this->assertEquals('foo', $instance->getValue());
    $this->assertEquals('bar', $instance->addCacheTags());
  }

}
