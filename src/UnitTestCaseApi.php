<?php

namespace Drupal\test_helpers;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\test_helpers\Traits\SingletonTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Proxying public calls of UnitTestCase API to private methods.
 */
class UnitTestCaseApi extends UnitTestCase {
  use SingletonTrait;

  /**
   * {@inheritdoc}
   */
  public function getRandomGenerator() {
    return parent::getRandomGenerator();
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerWithCacheTagsInvalidator(CacheTagsInvalidatorInterface $cache_tags_validator) {
    return parent::getContainerWithCacheTagsInvalidator($cache_tags_validator);
  }

  /**
   * {@inheritdoc}
   */
  public function getClassResolverStub() {
    return parent::getClassResolverStub();
  }

  /**
   * {@inheritdoc}
   */
  public function createMock(string $originalClassName): MockObject {
    return parent::createMock($originalClassName);
  }

  /**
   * {@inheritdoc}
   */
  public function createPartialMock(string $originalClassName, array $methods): MockObject {
    return parent::createPartialMock($originalClassName, $methods);
  }

}
