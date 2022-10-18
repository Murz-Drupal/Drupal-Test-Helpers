<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Query\ConditionInterface as QueryConditionInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\ConditionInterface as EntityQueryConditionInterface;
use Drupal\test_helpers\Stub\ModuleHandlerStub;
use Drupal\test_helpers\Stub\TokenStub;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MethodNameNotConfiguredException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Yaml\Yaml;

// This trick is to prevent 'Undefined constant' warnings in code sniffers.
defined('DRUPAL_ROOT') || define('DRUPAL_ROOT', '');
/**
 * Helper functions to simplify writing of Unit Tests.
 */
class UnitTestHelpers {

  /**
   * The list of implemented custom stubs for services.
   */
  const SERVICES_CUSTOM_STUBS = [
    'entity_type.manager' => EntityTypeManagerStub::class,
    'database' => DatabaseStub::class,
    'token' => TokenStub::class,
    'module_handler' => ModuleHandlerStub::class,
  ];

  /**
   * The list of implemented custom stubs for services.
   */
  const SERVICES_CUSTOM_STUBS_CALLBACKS = [
    'string_translation' => [self::class, 'getStringTranslationStub'],
    'class_resolver' => [self::class, 'getClassResolverStub'],
  ];

  /**
   * Gets a protected method from a class using reflection.
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
   * Calls a protected method from a class using reflection.
   */
  public static function callProtectedMethod(object $class, string $methodName, array $arguments = []) {
    $method = self::getProtectedMethod($class, $methodName);
    return $method->invokeArgs($class, $arguments);
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
   * Gets a mocked method from the Mock object to replace return value.
   *
   * This allows to replace the return value of the already defined method via
   * `$mockedMethod->willReturn('New Value')`.
   *
   * It's not possible with PHPUnit API, but here is a feature request about it:
   * https://github.com/sebastianbergmann/phpunit/issues/5070 - please vote!
   */
  public static function getMockedMethod(MockObject $mock, string $method) {
    $invocationHandler = $mock->__phpunit_getInvocationHandler();
    $configurableMethods = self::getProtectedProperty($invocationHandler, 'configurableMethods');
    $matchers = self::getProtectedProperty($invocationHandler, 'matchers');
    foreach ($matchers as $matcher) {
      $methodNameRuleObject = self::getProtectedProperty($matcher, 'methodNameRule');
      if ($methodNameRuleObject->matchesName($method)) {
        return new InvocationMocker(
            $invocationHandler,
            $matcher,
            ...$configurableMethods
        );
      }
    }
    throw new MethodNameNotConfiguredException();
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
   * Binds a closure function to a mocked class method.
   */
  public static function bindClosureToClassMethod(\Closure $closure, MockObject $class, string $method): void {
    $doClosure = $closure->bindTo($class, get_class($class));
    $class->method($method)->willReturnCallback($doClosure);
  }

  /**
   * Tests simple create() and __construct() functions.
   *
   * @param string|object $class
   *   The class to test, can be a string with path or initialized class.
   * @param array $createArguments
   *   The list of arguments for passing to function create().
   *
   * @return object
   *   The initialized class instance.
   */
  public static function createService($class, array $createArguments = []): object {
    $container = UnitTestHelpers::getContainer();
    $classInstance = $class::create($container, ...$createArguments);
    $className = is_string($class) ? $class : get_class($class);
    Assert::assertInstanceOf($className, $classInstance);
    return $classInstance;
  }

  /**
   * Gets a Drupal services container, or creates a new one.
   */
  public static function getContainer($forceCreate = FALSE): object {
    $container = (!$forceCreate && \Drupal::hasContainer())
      ? \Drupal::getContainer()
      : new ContainerBuilder();
    \Drupal::setContainer($container);
    return $container;
  }

  /**
   * Initializes a new service and adds to the Drupal container, if not exists.
   */
  public static function initService(string $serviceName, $class = NULL, bool $override = FALSE): object {
    $container = self::getContainer();
    $currentService = $container->has($serviceName)
      ? $container->get($serviceName)
      : new \stdClass();
    if ($class === NULL) {
      $class = self::getServiceStub($serviceName);
    }
    elseif (is_string($class)) {
      $class = self::createMock($class);
    }
    elseif (!is_object($class)) {
      throw new \Exception("Class should be an object, string as path to class, or NULL.");
    }
    if (
      (get_class($currentService) !== get_class($class))
      || $override
    ) {
      $container->set($serviceName, $class);
    }
    return $container->get($serviceName);
  }

  /**
   * Initializes services with creating mocks/stubs for not passed classes.
   */
  public static function initServices(array $services, $clearContainer = FALSE): void {
    if ($clearContainer) {
      UnitTestHelpers::getContainer(TRUE);
    }
    foreach ($services as $key => $value) {
      // If we have only a service name - just reuse the default behavior.
      if (is_int($key)) {
        self::initService($value);
      }
      // If we have a service name in key and class in value - pass the class.
      else {
        self::initService($key, $value);
      }
    }
  }

  /**
   * Creates a mock for a service with getting definition from a YAML file.
   */
  public static function createServiceMock(string $serviceName, string $servicesYamlFile = NULL): MockObject {
    $serviceClass = self::getServiceClassByName($serviceName, $servicesYamlFile);
    $service = UnitTestHelpers::createMock($serviceClass);
    self::initService($serviceName, $service);
    return $service;
  }

  /**
   * Gets a service stub: custom stub or just a mock for a default Drupal class.
   */
  public static function getServiceStub(string $serviceName, bool $onlyCustomMocks = FALSE): object {
    $container = UnitTestHelpers::getContainer();
    if ($container->has($serviceName)) {
      return $container->get($serviceName);
    }
    $service = self::getServiceStubClass($serviceName, $onlyCustomMocks);
    $container->set($serviceName, $service);
    return $service;
  }

  /**
   * Gets a service class by name, using Drupal defaults or a custom YAML file.
   */
  public static function getServiceClassByName(string $serviceName, string $servicesYamlFile = NULL): string {
    if ($servicesYamlFile) {
      $services = Yaml::parseFile(DRUPAL_ROOT . '/' . $servicesYamlFile)['services'];
      $serviceClass = $services[$serviceName]['class'] ?? FALSE;
    }
    else {
      require_once dirname(__FILE__) . '/includes/DrupalCoreServicesMap.data';
      // This trick is to prevent 'Undefined constant' warnings in code sniffers.
      defined('DRUPAL_CORE_SERVICES_MAP') || define('DRUPAL_CORE_SERVICES_MAP', '');
      $serviceClass = DRUPAL_CORE_SERVICES_MAP[$serviceName] ?? FALSE;
    }
    if (!$serviceClass) {
      throw new \Exception("Service '$serviceName' is missing in the list.");
    }
    return $serviceClass;
  }

  /**
   * Creates a stub for an entity from a given class.
   */
  public static function createEntityStub(string $entityClassName, array $values = [], array $options = []): EntityInterface {
    $entityStorage = self::getEntityStorageStub($entityClassName);
    return $entityStorage->stubCreateEntity($entityClassName, $values, $options);
  }

  /**
   * Gets or initializes an Entity Storage for a given Entity class name.
   */
  public static function getEntityStorageStub(string $entityClassName): EntityStorageStub {
    return self::getServiceStub('entity_type.manager')->stubGetOrCreateStorage($entityClassName);
  }

  /**
   * Initializes the main services to get mocks for entities.
   */
  public static function initEntityTypeManagerStubs(): void {
    self::getServiceStub('entity_type.manager');
  }

  /* ************************************************************************ *
   * Helpers for queries.
   * ************************************************************************ */

  /**
   * Performs matching of passed conditions with the query.
   */
  public static function queryIsSubsetOf(object $query, object $queryExpected, $onlyListed = FALSE): bool {
    // @todo add checks for range, sort and other query parameters.
    return self::matchConditions($query->condition, $queryExpected->condition, $onlyListed);
  }

  /**
   * Performs matching of passed conditions with the query.
   */
  public static function matchConditions(object $conditionsObject, object $conditionsExpectedObject, $onlyListed = FALSE): bool {
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
          return self::matchConditions($condition['field'], $conditionExpected['field'], $onlyListed);
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

  /* ************************************************************************ *
   * Wrappers for UnitTestCase functions to make them available statically.
   * ************************************************************************ */

  /**
   * Gets the random generator for the utility methods.
   */
  public static function getRandomGenerator() {
    return UnitTestCaseWrapper::getInstance()->getRandomGenerator();
  }

  /**
   * Sets up a container with a cache tags invalidator.
   */
  public static function getContainerWithCacheTagsInvalidator(CacheTagsInvalidatorInterface $cache_tags_validator) {
    return UnitTestCaseWrapper::getInstance()->getContainerWithCacheTagsInvalidator($cache_tags_validator);
  }

  /**
   * Returns a stub class resolver.
   */
  public static function getClassResolverStub() {
    return UnitTestCaseWrapper::getInstance()->getClassResolverStub();
  }

  /**
   * Returns a stub translation manager that just returns the passed string.
   */
  public static function getStringTranslationStub() {
    return UnitTestCaseWrapper::getInstance()->getStringTranslationStub();
  }

  /**
   * Returns a mock object for the specified class.
   */
  public static function createMock(string $originalClassName): MockObject {
    return UnitTestCaseWrapper::getInstance()->createMock($originalClassName);
  }

  /**
   * Returns a partial mock object for the specified class.
   */
  public static function createPartialMock(string $originalClassName, array $methods): MockObject {
    return UnitTestCaseWrapper::getInstance()->createPartialMock($originalClassName, $methods);
  }

  /* ************************************************************************ *
   * UnitTestCase additions.
   * ************************************************************************ */

  /**
   * Creates a partial mock for the class and call constructor with arguments.
   */
  public static function createPartialMockWithConstructor(string $originalClassName, array $methods, array $constructorArgs = [], array $addMethods = NULL): MockObject {
    return UnitTestCaseWrapper::getInstance()->createPartialMockWithConstructor($originalClassName, $methods, $constructorArgs, $addMethods);
  }

  /**
   * Creates a partial mock with ability to add custom methods.
   */
  public static function createPartialMockWithCustomMethods(string $originalClassName, array $methods, array $addMethods = NULL): MockObject {
    return UnitTestCaseWrapper::getInstance()->createPartialMockWithCustomMethods($originalClassName, $methods, $addMethods);
  }

  /* ************************************************************************ *
   * Internal functions.
   * ************************************************************************ */

  /**
   * Internal callback helper function for array_uintersect.
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
   * Gets the class for a service, including current module implementations.
   */
  private static function getServiceStubClass(string $serviceName, bool $onlyCustomMocks = FALSE): object {
    if (isset(self::SERVICES_CUSTOM_STUBS[$serviceName])) {
      $serviceClass = self::SERVICES_CUSTOM_STUBS[$serviceName];
      $service = new $serviceClass();
    }
    elseif (isset(self::SERVICES_CUSTOM_STUBS_CALLBACKS[$serviceName])) {
      $serviceClassCallback = self::SERVICES_CUSTOM_STUBS_CALLBACKS[$serviceName];
      $service = call_user_func_array($serviceClassCallback, []);
    }
    elseif ($onlyCustomMocks) {
      throw new ServiceNotFoundException($serviceName);
    }
    else {
      $service = UnitTestHelpers::createServiceMock($serviceName);
    }
    return $service;
  }

  /**
   * Disables a construtor calls to allow only static calls.
   */
  private function __construct() {
  }

  /* ************************************************************************ *
   * Deprecations.
   * ************************************************************************ */

  /**
   * Tests simple create() and __construct() functions.
   *
   * @deprecated in test_helpers:1.0.0-alpha7 and is removed from
   *   test_helpers:1.0.0-beta1. Use UnitTestHelpers::initService().
   *
   * @see https://www.drupal.org/project/test_helpers/issues/3315975
   */
  public static function doTestCreateAndConstruct($class, array $createArguments = []): object {
    return self::createService($class, $createArguments);
  }

  /**
   * Adds a new service to the Drupal container, if exists - reuse existing.
   *
   * @deprecated in test_helpers:1.0.0-alpha7 and is removed from
   *   test_helpers:1.0.0-beta1. Use UnitTestHelpers::initService().
   *
   * @see https://www.drupal.org/project/test_helpers/issues/3315975
   */
  public static function addToContainer(string $serviceName, $class = NULL, bool $override = FALSE): object {
    @trigger_error('Function addToContainer is renamed to initService in test_helpers:1.0.0-alpha7.', E_USER_DEPRECATED);
    return self::initService($serviceName, $class, $override);
  }

}
