<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\test_helpers\Traits\SingletonTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Helpers for TVH Unit tests.
 */
class UnitTestHelpers extends UnitTestCase {
  use SingletonTrait {
    __construct as __originalConstruct;
  }

  /**
   * Gets an accessible method from class using reflection.
   */
  public static function getAccessibleMethod(object|string $className, string $methodName): \ReflectionMethod {
    $class = new \ReflectionClass($className);
    $method = $class
      ->getMethod($methodName);
    $method
      ->setAccessible(TRUE);
    return $method;
  }

  /**
   * Parses the annotation for a class and gets the definition.
   */
  public static function getPluginDefinition(string $class, string $plugin, string $annotationName = NULL) {
    static $definitions;

    if (isset($definitions[$plugin][$class])) {
      return $definitions[$plugin][$class];
    }

    $rc = new \ReflectionClass($class);

    $reader = new SimpleAnnotationReader();
    $reader->addNamespace('Drupal\Core\Annotation');
    $reader->addNamespace('Drupal\Core\\' . $plugin . '\Annotation');

    // If no annotation name is passed, just getting the first anotation.
    if (!$annotationName) {
      $annotation = current($reader->getClassAnnotations($rc));
    }
    else {
      $annotation = $reader->getClassAnnotation($rc, $annotationName);
    }
    if ($annotation) {
      // Inline copy of the proteced function
      // AnnotatedClassDiscovery::prepareAnnotationDefinition().
      $annotation->setClass($class);

      $definition = $annotation->get();

      return $definition;
    }
  }

  /**
   * Adds a new service to the Drupal container, if exists - reuse existing.
   */
  public static function addToContainer(string $serviceName, object $class, bool $override = FALSE): ?object {
    $container = \Drupal::hasContainer()
      ? \Drupal::getContainer()
      : new ContainerBuilder();
    $currentService = $container->has($serviceName)
      ? $container->get($serviceName)
      : new \stdClass();
    if (
      (get_class($currentService) !== get_class($class))
      || $override
    ) {
      $container->set($serviceName, $class);
    }
    \Drupal::setContainer($container);

    return $container->get($serviceName);
  }

  /**
   * Gets the Drupal services container, or creates a new one.
   */
  public static function getContainerOrCreate(): object {
    $container = \Drupal::hasContainer()
      ? \Drupal::getContainer()
      : new ContainerBuilder();
    return $container;
  }

  /**
   * Gets the service from the Drupal container, or creates a new one.
   */
  public static function getFromContainerOrCreate(string $serviceName, object $class): object {
    $container = self::getContainerOrCreate();
    if (!$container->has($serviceName)) {
      $container->set($serviceName, $class);
      \Drupal::setContainer($container);
    }
    return $container->get($serviceName);
  }

  /**
   * Creates a partial mock for the class and call constructor with arguments.
   */
  public function createPartialMockWithCostructor(string $originalClassName, array $methods, array $constructorArgs = [], array $addMethods = NULL): MockObject {
    $mockBuilder = $this->getMockBuilder($originalClassName)
      ->setConstructorArgs($constructorArgs)
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      // ->enableProxyingToOriginalMethods()
      ->onlyMethods(empty($methods) ? NULL : $methods);
    if (!empty($addMethods)) {
      $mockBuilder->addMethods($addMethods);
    }
    return $mockBuilder->getMock();
  }

  /**
   * Binds a closure function to a mocked class method.
   */
  public static function bindClosureToClassMethod(\Closure $closure, MockObject $class, string $method): void {
    $doClosure = $closure->bindTo($class, get_class($class));
    $class->method($method)->willReturnCallback($doClosure);
  }

  /**
   * Tests simple create() and __construct() functions.
   */
  public function doTestCreateAndConstruct(string $class, array $createArguments = []): object {
    $container = self::getContainerOrCreate();
    $classInstance = $class::create($container, ...$createArguments);
    $this->assertInstanceOf($class, $classInstance);
    return $classInstance;
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


}
