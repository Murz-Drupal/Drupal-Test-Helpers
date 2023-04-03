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
  // To suppress "Possible useless method overriding detected" warning.
  // @codingStandardsIgnoreStart
  public function getRandomGenerator() {
    return parent::getRandomGenerator();
  }
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  // To suppress "Possible useless method overriding detected" warning.
  // @codingStandardsIgnoreStart
  public function getContainerWithCacheTagsInvalidator(CacheTagsInvalidatorInterface $cache_tags_validator) {
    return parent::getContainerWithCacheTagsInvalidator($cache_tags_validator);
  }
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  // To suppress "Possible useless method overriding detected" warning.
  // @codingStandardsIgnoreStart
  public function getClassResolverStub() {
    return parent::getClassResolverStub();
  }
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  // To suppress "Possible useless method overriding detected" warning.
  // @codingStandardsIgnoreStart
  public function createMock(string $originalClassName): MockObject {
    return parent::createMock($originalClassName);
  }
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  // To suppress "Possible useless method overriding detected" warning.
  // @codingStandardsIgnoreStart
  public function createPartialMock(string $originalClassName, array $methods): MockObject {
    return parent::createPartialMock($originalClassName, $methods);
  }
  // @codingStandardsIgnoreEnd

  /**
   * Creates a partial mock for the class and call constructor with arguments.
   *
   * @param string $originalClassName
   *   The name of the class to mock.
   * @param array $methods
   *   An array of methods to mock.
   * @param array $constructorArgs
   *   An array of arguments for the constructor.
   * @param array $addMethods
   *   An array with new methods to add into the mock.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked object
   */
  public function createPartialMockWithConstructor(string $originalClassName, array $methods = NULL, array $constructorArgs = NULL, array $addMethods = NULL): MockObject {
    $mockBuilder = $this->getMockBuilder($originalClassName)
      ->setConstructorArgs($constructorArgs ?? [])
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes();
    if (!empty($methods)) {
      $mockBuilder->onlyMethods($methods);
    }
    if (!empty($addMethods)) {
      $mockBuilder->addMethods($addMethods);
    }
    // @todo Try to add enableProxyingToOriginalMethods() function.
    return $mockBuilder->getMock();
  }

  /**
   * Creates a partial mock with ability to add custom methods.
   *
   * @param string $originalClassName
   *   The name of the class to mock.
   * @param array $methods
   *   An array of methods to mock.
   * @param array $addMethods
   *   An array with new methods to add into the mock.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked object
   */
  public function createPartialMockWithCustomMethods(string $originalClassName, array $methods = NULL, array $addMethods = NULL): MockObject {
    $mockBuilder = $this->getMockBuilder($originalClassName)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->allowMockingUnknownTypes();
    if (!empty($methods)) {
      $mockBuilder->onlyMethods($methods);
    }
    if (!empty($addMethods)) {
      $mockBuilder->addMethods($addMethods);
    }
    // @todo Try to add enableProxyingToOriginalMethods() function.
    return $mockBuilder->getMock();
  }

}
