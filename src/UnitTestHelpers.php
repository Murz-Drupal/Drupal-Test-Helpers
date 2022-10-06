<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Query\ConditionInterface as QueryConditionInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Query\ConditionInterface as EntityQueryConditionInterface;
use Drupal\test_helpers\Traits\SingletonTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Yaml\Yaml;

/**
 * Helpers for TVH Unit tests.
 */
class UnitTestHelpers extends UnitTestCase {
  use SingletonTrait {
    // This trick is to allow use of the class not as a singleton too.
    __construct as __originalConstruct;
  }

  /**
   * A dummy constructor.
   */
  public function __construct() {
  }

  /**
   * Gets protected method from a class using reflection.
   */
  public static function getProtectedMethod(object $class, string $methodName): \ReflectionMethod {
    $reflection = new \ReflectionClass($class);
    $method = $reflection
      ->getMethod($methodName);
    $method
      ->setAccessible(TRUE);
    return $method;
  }

  /**
   * Gets a protected property from a class using reflection.
   */
  public static function getProtectedProperty(object $class, string $propertyName) {
    $reflection = new \ReflectionClass($class);
    $property = $reflection
      ->getProperty($propertyName);
    $property
      ->setAccessible(TRUE);
    return $property->getValue($class);
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

  /**
   * Performs matching of passed conditions with the query.
   */
  public static function matchConditions(object $conditionsExpectedObject, object $conditionsObject, $onlyListed = FALSE): bool {
    if ($conditionsObject instanceof EntityQueryConditionInterface) {
      if (strcasecmp($conditionsObject->getConjunction(), $conditionsExpectedObject->getConjunction()) != 0) {
        return FALSE;
      }
    }
    elseif ($conditionsObject instanceof QueryConditionInterface) {
      if (strcasecmp($conditionsObject->conditions()['#conjunction'], $conditionsExpectedObject->conditions()['#conjunction']) != 0) {
        return FALSE;
      }
    }
    else {
      throw new \Exception("Conditions should implement Drupal\Core\Entity\Query\ConditionInterface or Drupal\Core\Database\Query\ConditionInterface.");
    }
    $conditions = $conditionsObject->conditions();
    unset($conditions['#conjunction']);
    $conditionsExpected = $conditionsExpectedObject->conditions();
    unset($conditionsExpected['#conjunction']);
    $conditionsFound = [];
    foreach ($conditions as $condition) {
      foreach ($conditionsExpected as $conditionsExpectedDelta => $conditionExpected) {
        if (is_object($condition['field']) || is_object($conditionExpected['field'])) {
          if (!is_object($condition['field']) || !is_object($conditionExpected['field'])) {
            continue;
          }
          return self::matchConditions($conditionExpected['field'], $condition['field'], $onlyListed);
        }
        if (self::isNestedArraySubsetOf($condition, $conditionExpected)) {
          $conditionsFound[$conditionsExpectedDelta] = TRUE;
        }
      }
    }
    if (count($conditionsFound) != count($conditionsExpected)) {
      return FALSE;
    }
    if ($onlyListed && (count($conditions) != count($conditionsExpected))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Performs check if the actial array is a subset of expected.
   */
  public static function isNestedArraySubsetOf($array, $subset): bool {
    if (!is_array($array) || !is_array($subset)) {
      return FALSE;
    }
    $result = array_uintersect_assoc($subset, $array, self::class . '::isValueSubsetOfCallback');
    return $result == $subset;
  }

  /**
   * Internal callback helper function for array_uintersect.
   *
   * Should be public to be available as a callback.
   */
  private static function isValueSubsetOfCallback($value, $expected): int {
    // The callback function for array_uintersect should return
    // integer instead of bool (-1, 0, 1).
    if (is_array($expected)) {
      return self::isNestedArraySubsetOf($value, $expected) ? 0 : -1;
    }
    return ($value == $expected) ? 0 : -1;
  }

  /**
   * {@inheritdoc}
   */
  public function createServiceMock(string $serviceName, string $servicesYamlFile = NULL): MockObject {
    if ($servicesYamlFile) {
      $services = Yaml::parseFile(DRUPAL_ROOT . '/' . $servicesYamlFile)['services'];
      $serviceClass = $services[$serviceName]['class'] ?? FALSE;
    }
    else {
      require_once dirname(__FILE__) . '/DrupalCoreServicesMap.inc.php';
      $serviceClass = DRUPAL_CORE_SERVICES_MAP[$serviceName] ?? FALSE;
    }
    if (!$serviceClass) {
      throw new \Exception("Service '$serviceName' is missing in the list.");
    }
    $service = $this->createMock($serviceClass);
    self::addToContainer($serviceName, $service);
    return $service;
  }

}
