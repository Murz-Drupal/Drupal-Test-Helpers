<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Helpers for TVH Unit tests.
 */
class UnitTestHelpers extends UnitTestCase {

  /**
   * Gets an accessible method from class using reflection.
   */
  public static function getAccessibleMethod(\stdclass $className, string $methodName): \ReflectionMethod {
    $class = new \ReflectionClass($className);
    $method = $class
      ->getMethod($methodName);
    $method
      ->setAccessible(TRUE);
    return $method;
  }

  /**
   * Parses the annotation for a Drupal Plugin class and generates a definition.
   */
  public static function getPluginDefinition(string $class, string $plugin): PluginDefinition {
    static $definitions;

    if (isset($definitions[$plugin][$class])) {
      return $definitions[$plugin][$class];
    }

    $rc = new \ReflectionClass($class);

    $reader = new SimpleAnnotationReader();
    $reader->addNamespace('Drupal\Core\Annotation');
    $reader->addNamespace('Drupal\Core\\' . $plugin . '\Annotation');
    $annotation = current($reader->getClassAnnotations($rc));
    $definition = $annotation->get();
    $definitions[$plugin][$class] = $definition;
    return $definition;
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
   * Gets the service from the Drupal container, or creates a new one.
   */
  public static function getFromContainerOrCreate(string $serviceName, object $class): ?object {
    $container = \Drupal::hasContainer()
      ? \Drupal::getContainer()
      : new ContainerBuilder();
    if (!$container->has($serviceName)) {
      $container->set($serviceName, $class);
      \Drupal::setContainer($container);
    }
    return $container->get($serviceName);
  }

  /**
   * Creates a partial mock for the class and call constructor with arguments.
   */
  public function createPartialMockWithCostructor(string $originalClassName, array $methods, array $constructorArgs): MockObject {
    return $this->getMockBuilder($originalClassName)
      ->setConstructorArgs($constructorArgs)
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->onlyMethods(empty($methods) ? NULL : $methods)
      // ->enableProxyingToOriginalMethods()
      ->getMock();
  }

}
