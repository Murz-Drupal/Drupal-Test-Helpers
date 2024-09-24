<?php

namespace Drupal\test_helpers;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * A singleton class to provide UnitTestCase private functions as public.
 */
class UnitTestCaseWrapper extends UnitTestCase {

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
  public function createPartialMockWithConstructor(string $originalClassName, ?array $methods = NULL, ?array $constructorArgs = NULL, ?array $addMethods = NULL): MockObject {
    $mockBuilder = $this->getMockBuilder($originalClassName)
      ->setConstructorArgs($constructorArgs ?? [])
      ->disableOriginalClone();
    if (!empty($methods)) {
      $mockBuilder->onlyMethods($methods);
    }
    if (!empty($addMethods)) {
      // @todo Reimplement this function locally if the author removes it.
      // @see https://github.com/sebastianbergmann/phpunit/issues/5320
      // @phpstan-ignore-next-line We need this function.
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
  public function createPartialMockWithCustomMethods(string $originalClassName, ?array $methods = NULL, ?array $addMethods = NULL): MockObject {
    $mockBuilder = $this->getMockBuilder($originalClassName)
      ->disableOriginalConstructor()
      ->disableOriginalClone();
    if (!empty($methods)) {
      $mockBuilder->onlyMethods($methods);
    }
    if (!empty($addMethods)) {
      // @todo Reimplement this function locally if the author removes it.
      // @see https://github.com/sebastianbergmann/phpunit/issues/5320
      // @phpstan-ignore-next-line
      $mockBuilder->addMethods($addMethods);
    }
    // @todo Try to add enableProxyingToOriginalMethods() function.
    return $mockBuilder->getMock();
  }

  /**
   * The class instance.
   *
   * @var static
   */
  private static $instance = NULL;

  /**
   * Gets the instance via lazy initialization (created on first usage).
   *
   * @return static
   */
  public static function getInstance() {
    if (!self::$instance) {
      $c = get_called_class();
      self::$instance = new $c('UnitTestCaseWrapper');
    }

    return self::$instance;
  }

}
