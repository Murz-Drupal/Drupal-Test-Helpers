<?php

namespace Drupal\test_helpers;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\test_helpers\includes\SingletonTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * A singleton class to provide UnitTestCase private functions as public.
 */
class UnitTestCaseWrapper extends UnitTestCase {
  use SingletonTrait {
    getInstance as _getInstance;
  }

  /**
   * Gets the instance via lazy initialization (created on first usage).
   */
  public static function getInstance(): UnitTestCaseWrapper {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

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

  /**
   * Creates a partial mock for the class and call constructor with arguments.
   */
  public function createPartialMockWithConstructor(string $originalClassName, array $methods = [], array $constructorArgs = [], array $addMethods = NULL): MockObject {
    $mockBuilder = $this->getMockBuilder($originalClassName)
      ->setConstructorArgs($constructorArgs)
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      // ->enableProxyingToOriginalMethods()
      ->onlyMethods($methods);
    if (!empty($addMethods)) {
      $mockBuilder->addMethods($addMethods);
    }
    return $mockBuilder->getMock();
  }

  /**
   * Creates a partial mock with ability to add custom methods.
   */
  public function createPartialMockWithCustomMethods(string $originalClassName, array $methods = [], array $addMethods = []): MockObject {
    $mockBuilder = $this->getMockBuilder($originalClassName)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->allowMockingUnknownTypes()
      // ->enableProxyingToOriginalMethods()
      ->onlyMethods($methods);
    if (!empty($addMethods)) {
      $mockBuilder->addMethods($addMethods);
    }
    return $mockBuilder->getMock();
  }

}
