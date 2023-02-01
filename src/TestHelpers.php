<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\MemoryBackendFactory;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\Database\Query\ConditionInterface as DatabaseQueryConditionInterface;
use Drupal\Core\Database\Query\SelectInterface as DatabaseSelectInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\ConditionInterface as EntityQueryConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface as EntityQueryInterface;
use Drupal\test_helpers\lib\TestHelpersStaticStorageService;
use Drupal\test_helpers\Stub\ConfigFactoryStub;
use Drupal\test_helpers\Stub\DatabaseStub;
use Drupal\test_helpers\Stub\DateFormatterStub;
use Drupal\test_helpers\Stub\EntityTypeBundleInfoStub;
use Drupal\test_helpers\Stub\EntityTypeManagerStub;
use Drupal\test_helpers\Stub\LanguageManagerStub;
use Drupal\test_helpers\Stub\LoggerChannelFactoryStub;
use Drupal\test_helpers\Stub\ModuleHandlerStub;
use Drupal\test_helpers\Stub\TokenStub;
use Drupal\test_helpers\Stub\TypedDataManagerStub;
use Drupal\test_helpers\StubFactory\EntityStubFactory;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MethodNameNotConfiguredException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

// This trick is to prevent 'Undefined constant' warnings in code sniffers.
defined('DRUPAL_ROOT') || define('DRUPAL_ROOT', '');

// These constants are used in some entity definitions from core.
// They're defined in system.module, so just re-define them here.
defined('DRUPAL_DISABLED') || define('DRUPAL_DISABLED', 0);
defined('DRUPAL_OPTIONAL') || define('DRUPAL_OPTIONAL', 1);
defined('DRUPAL_REQUIRED') || define('DRUPAL_REQUIRED', 2);
defined('REGIONS_VISIBLE') || define('REGIONS_VISIBLE', 'visible');
defined('REGIONS_ALL') || define('REGIONS_ALL', 'all');

/**
 * Helper functions to simplify writing of Unit Tests.
 */
class TestHelpers {

  /**
   * The array of implemented custom stubs for services.
   *
   * Key: a service name.
   * Value: a service class, or a callback function to initialize an instance
   * as array in format "[className, functionName]".
   */
  public const SERVICES_CUSTOM_STUBS = [
    'test_helpers.static_storage' => TestHelpersStaticStorageService::class,
    'cache.backend.memory' => MemoryBackendFactory::class,
    'class_resolver' => [self::class, 'getClassResolverStub'],
    'config.factory' => ConfigFactoryStub::class,
    'database' => DatabaseStub::class,
    'date.formatter' => DateFormatterStub::class,
    'entity_type.bundle.info' => EntityTypeBundleInfoStub::class,
    'entity_type.manager' => EntityTypeManagerStub::class,
    'entity.memory_cache' => MemoryCache::class,
    'language_manager' => LanguageManagerStub::class,
    'logger.factory' => LoggerChannelFactoryStub::class,
    'module_handler' => ModuleHandlerStub::class,
    'string_translation' => [self::class, 'getStringTranslationStub'],
    'token' => TokenStub::class,
    'transliteration' => PhpTransliteration::class,
    'typed_data_manager' => TypedDataManagerStub::class,
    'uuid' => Php::class,
  ];

  /**
   * Gets a protected method from a class using reflection.
   *
   * @param object $class
   *   The class instance.
   * @param string $methodName
   *   The name of the method to get.
   *
   * @return ReflectionMethod
   *   The method instance.
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
   *
   * @param object $class
   *   The class instance.
   * @param string $methodName
   *   The name of the method to get.
   * @param array $arguments
   *   The list of aruments for the calling method.
   *
   * @return mixed
   *   The return value of the executed function.
   */
  public static function callProtectedMethod(object $class, string $methodName, array $arguments = []) {
    $method = self::getProtectedMethod($class, $methodName);
    return $method->invokeArgs($class, $arguments);
  }

  /**
   * Gets a protected property from a class using reflection.
   *
   * @param object $class
   *   The class instance.
   * @param string $propertyName
   *   The name of the property to get.
   * @param bool $returnReflectionProperty
   *   Flag to return a ReflectionProperty object instead of value.
   *
   * @return mixed
   *   The property value.
   */
  public static function getProtectedProperty(object $class, string $propertyName, $returnReflectionProperty = FALSE) {
    $reflection = new \ReflectionClass($class);
    $property = $reflection
      ->getProperty($propertyName);
    $property
      ->setAccessible(TRUE);
    if ($returnReflectionProperty) {
      return $property;
    }
    return $property->getValue($class);
  }

  /**
   * Sets a protected property value in a class using reflection.
   *
   * @param object $class
   *   The class instance.
   * @param string $propertyName
   *   The name of the property to get.
   * @param mixed $value
   *   The value to set.
   */
  public static function setProtectedProperty(object $class, string $propertyName, $value): void {
    $reflection = new \ReflectionClass($class);
    $property = $reflection
      ->getProperty($propertyName);
    $property
      ->setAccessible(TRUE);
    $property->setValue($class, $value);
  }

  /**
   * Sets a closure function to a class method.
   *
   * This makes private class methods accessible inside the function via $this.
   *
   * @param \PHPUnit\Framework\MockObject\MockObject $class
   *   The mocked class.
   * @param string $method
   *   The method name.
   * @param \Closure $closure
   *   The closure function to bind.
   */
  public static function setMockedClassMethod(MockObject $class, string $method, \Closure $closure): void {
    $doClosure = $closure->bindTo($class, get_class($class));
    $class->method($method)->willReturnCallback($doClosure);
  }

  /**
   * Gets a mocked method from the Mock object to replace return value.
   *
   * This allows to replace the return value of the already defined method via
   * `$mockedMethod->willReturn('New Value')`.
   *
   * It's not possible with PHPUnit API, but here is a feature request about it:
   * https://github.com/sebastianbergmann/phpunit/issues/5070 - please vote!
   *
   * @param \PHPUnit\Framework\MockObject\MockObject $mock
   *   A mocked object.
   * @param string $method
   *   A method to get.
   *
   * @return \PHPUnit\Framework\MockObject\Builder\InvocationMocker
   *   An InvocationMocker object with the method.
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
   *
   * @param string $class
   *   A class name to get definition.
   * @param string $plugin
   *   A plugin id.
   * @param string $annotationName
   *   The name of an annotation to use.
   *
   * @return mixed
   *   The definitoin from the plugin.
   */
  public static function getPluginDefinition(string $class, string $plugin = 'TypedData', string $annotationName = NULL) {
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
   * Creates a class via calling function create() with container.
   *
   * @param string|object $class
   *   The class to test, can be a string with path or initialized class.
   * @param array $createArguments
   *   The list of arguments for passing to function create().
   * @param array $services
   *   The array of services to add to the container.
   *   Format is same as in function setServices().
   *
   * @return object
   *   The initialized class instance.
   */
  public static function createClass($class, array $createArguments = NULL, array $services = NULL): object {
    if ($services !== NULL) {
      self::setServices($services);
    }
    $container = self::getContainer();
    $createArguments ??= [];
    $classInstance = $class::create($container, ...$createArguments);
    return $classInstance;
  }

  /**
   * Creates a service from YAML file with passing services as arguments.
   *
   * @param string $servicesYamlFile
   *   The path to the YAML file.
   * @param string $name
   *   The name of the service.
   * @param array $additionalArguments
   *   The array additional arguments to the service constructor.
   * @param array $services
   *   The array of services to add to the container.
   *   Format is same as in function setServices().
   *
   * @return object
   *   The initialized class instance.
   */
  public static function createServiceFromYaml(string $servicesYamlFile, string $name, array $additionalArguments = [], array $services = NULL): object {
    if ($services !== NULL) {
      self::setServices($services);
    }
    $serviceInfo = self::getServiceInfoFromYaml($servicesYamlFile, $name);
    $classArguments = [];
    foreach (($serviceInfo['arguments'] ?? []) as $argument) {
      if (substr($argument, 0, 1) == '@') {
        $classArguments[] = self::service(substr($argument, 1));
      }
      else {
        $classArguments[] = $argument;
      }
    }
    $classInstance = new $serviceInfo['class'](...$classArguments, ...$additionalArguments);
    return $classInstance;
  }

  /**
   * Gets a Drupal services container, or creates a new one.
   *
   * @param bool $forceCreate
   *   Force create a new container, even if already exists.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The initialized container.
   */
  public static function getContainer($forceCreate = FALSE): ContainerInterface {
    $container = (!$forceCreate && \Drupal::hasContainer())
      ? \Drupal::getContainer()
      : new ContainerBuilder();
    \Drupal::setContainer($container);
    return $container;
  }

  /**
   * Gets the service stub or mock, or initiates a new one if missing.
   *
   * @param string $serviceName
   *   The service name.
   * @param object|string|null $class
   *   The class to use in service, allowed different types:
   *   - object: attachs the initialized object to the service.
   *   - string: creates a mock of the class by passed name.
   *   - null: use stub from Test Heleprs of default class from Drupal Core.
   * @param bool $forceOverride
   *   Control overriding the service:
   *   - false: overrides only if the class names are different.
   *   - true: always overrides the class by a new instance.
   * @param array $mockableMethods
   *   The list of exist methods to make mokable.
   * @param array $addMockableMethods
   *   The list of new methods to make them mokable.
   *
   * @return object
   *   The initialised service object.
   */
  public static function service(string $serviceName, $class = NULL, bool $forceOverride = FALSE, array $mockableMethods = [], array $addMockableMethods = []): object {
    $container = self::getContainer();
    $currentService = $container->has($serviceName)
      ? $container->get($serviceName)
      : new \stdClass();
    if ($class === NULL) {
      $class = self::getServiceStub($serviceName, $mockableMethods, $addMockableMethods);
    }
    elseif (is_string($class)) {
      $class = self::createMock($class);
    }
    elseif (!is_object($class)) {
      throw new \Exception("Class should be an object, string as path to class, or NULL.");
    }
    if (
      (get_class($currentService) !== get_class($class))
      || $forceOverride
    ) {
      $container->set($serviceName, $class);
    }
    return $container->get($serviceName);
  }

  /**
   * Initializes list of services and adds them to the container.
   *
   * @param array $services
   *   The array with services, supports two formats:
   *   - non associative array with service names: adds default classes.
   *   - service name as key and object or null in value: adds the class to the
   *     service, use NULL to add default class.
   * @param bool $clearContainer
   *   Clears the Drupal container, if true.
   */
  public static function setServices(array $services, bool $clearContainer = FALSE): void {
    if ($clearContainer) {
      TestHelpers::getContainer(TRUE);
    }
    foreach ($services as $key => $value) {
      // If we have only a service name - just reuse the default behavior.
      if (is_int($key)) {
        self::service($value);
      }
      // If we have a service name in key and class in value - pass the class.
      else {
        self::service($key, $value);
      }
    }
  }

  /**
   * Creates a mock for a service with getting definition from a YAML file.
   *
   * @param string $serviceName
   *   The name of the service to mock.
   * @param string $servicesYamlFile
   *   The YAML file with service info. If empty - uses the default map of
   *   services classes from Drupal Core.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked service instance.
   */
  public static function createServiceMock(string $serviceName, string $servicesYamlFile = NULL): MockObject {
    $serviceClass = self::getServiceClassByName($serviceName, $servicesYamlFile);
    $service = TestHelpers::createMock($serviceClass);
    self::service($serviceName, $service);
    return $service;
  }

  /**
   * Gets a service stub: custom stub or just a mock for a default Drupal class.
   *
   * @param string $serviceName
   *   The service name.
   * @param array $mockableMethods
   *   The list of exist methods to make mokable.
   * @param array $addMockableMethods
   *   The list of new methods to make them mokable.
   *
   * @return object
   *   The stub for the service, or a mocked default class.
   */
  public static function getServiceStub(string $serviceName, array $mockableMethods = [], array $addMockableMethods = []): object {
    $container = TestHelpers::getContainer();
    if ($container->has($serviceName)) {
      return $container->get($serviceName);
    }
    $service = self::getServiceStubClass($serviceName, $mockableMethods, $addMockableMethods);
    $container->set($serviceName, $service);
    return $service;
  }

  /**
   * Creates a stub entity for an entity type from a given class.
   *
   * @param string $entityTypeClassName
   *   The entity type class.
   * @param array $values
   *   An array with entity values:
   *   - keys: field/property names.
   *   - values: the field/property values.
   * @param array $options
   *   The array of options:
   *   - entity_base_type: base type of the entity:
   *     ContentEntityType or ConfigEntityType, default is ContentEntityType.
   *   - @see \Drupal\test_helpers\StubFactory\EntityStubFactory::create()
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\test_helpers\StubFactory\EntityStubInterface
   *   The stub object for the entity.
   */
  public static function createEntity(string $entityTypeClassName, array $values = [], array $options = []) {
    switch ($options['entity_base_type'] ?? NULL) {
      default:
        $annotation = NULL;
        break;

      case 'ContentEntityType':
        $annotation = '\Drupal\Core\Entity\Annotation\ContentEntityType';
        break;

      case 'ConfigEntityType':
        $annotation = '\Drupal\Core\Entity\Annotation\ConfigEntityType';
        break;
    }
    unset($options['entity_base_type']);

    // Initiating entity storage.
    self::getEntityStorage($entityTypeClassName, $annotation);

    $entity = EntityStubFactory::create($entityTypeClassName, $values, $options);

    return $entity;
  }

  /**
   * Creates a stub entity for an entity type from a given class and saves it.
   *
   * @param string $entityTypeClassName
   *   The entity type class.
   * @param array $values
   *   An array with entity values:
   *   - keys: field/property names.
   *   - values: the field/property values.
   * @param array $options
   *   The array of options:
   *   - entity_base_type: base type of the entity:
   *     ContentEntityType or ConfigEntityType, default is ContentEntityType.
   *   - @see \Drupal\test_helpers\StubFactory\EntityStubFactory::create()
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\test_helpers\StubFactory\EntityStubInterface
   *   The stub object for the entity.
   */
  public static function saveEntity(string $entityTypeClassName, array $values = [], array $options = []) {
    $entity = self::createEntity($entityTypeClassName, $values, $options);
    $entity->save();
    return $entity;
  }

  /**
   * Gets or initializes an Entity Storage for a given Entity Type class name.
   *
   * @param string $entityTypeClassName
   *   The entity class.
   * @param string $annotation
   *   The annotation class.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The initialized stub of Entity Storage.
   */
  public static function getEntityStorage(string $entityTypeClassName, string $annotation = NULL): EntityStorageInterface {
    return self::getServiceStub('entity_type.manager')->stubGetOrCreateStorage($entityTypeClassName, $annotation);
  }

  /**
   * Initializes the main services to work with entities stubs.
   *
   * Initializes a bundle of services, required to work with entity stubs:
   * - entity_type.manager
   * - language_manager
   * - entity_field.manager
   * - entity.query.sql
   * - string_translation
   * - plugin.manager.field.field_type
   * - typed_data_manager
   * - uuid
   * Also adds them to the Drupal Container.
   */
  public static function initEntityTypeManagerStubs(): void {
    self::getServiceStub('entity_type.manager');
  }

  /* ************************************************************************ *
   * Helpers for queries.
   * ************************************************************************ */

  /**
   * Performs matching of passed conditions with the query.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface|Drupal\Core\Database\Query\ConditionInterface $query
   *   The query object to check.
   * @param \Drupal\Core\Entity\Query\ConditionInterface|Drupal\Core\Database\Query\ConditionInterface $queryExpected
   *   The query object with expected conditions.
   * @param bool $onlyListed
   *   Forces to return false, if the checking query object contains more
   *   conditions than in object with expected conditions.
   *
   * @return bool
   *   True if is subset, false if not.
   */
  public static function queryIsSubsetOf(object $query, object $queryExpected, $onlyListed = FALSE): bool {
    if ($query instanceof DatabaseSelectInterface && $queryExpected instanceof DatabaseSelectInterface) {
      $order = self::getProtectedProperty($query, 'order');
      $orderExpected = self::getProtectedProperty($queryExpected, 'order');
      if (!self::isNestedArraySubsetOf($order, $orderExpected)) {
        return FALSE;
      }

    }
    elseif ($query instanceof EntityQueryInterface && $queryExpected instanceof EntityQueryInterface) {
      if ($query->getEntityTypeId() != $queryExpected->getEntityTypeId()) {
        return FALSE;
      }
      $sort = self::getProtectedProperty($query, 'sort');
      $sortExpected = self::getProtectedProperty($queryExpected, 'sort');
      if (!self::isNestedArraySubsetOf($sort, $sortExpected)) {
        return FALSE;
      }
    }
    else {
      throw new \Exception('Unsupportable query types.');
    }
    $range = self::getProtectedProperty($query, 'range');
    $rangeExpected = self::getProtectedProperty($queryExpected, 'range');
    if (!self::isNestedArraySubsetOf($range, $rangeExpected)) {
      return FALSE;
    }

    $condition = self::getProtectedProperty($query, 'condition');
    $conditionExpected = self::getProtectedProperty($queryExpected, 'condition');
    if (!self::matchConditions($condition, $conditionExpected, $onlyListed)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Performs matching of passed conditions with the query.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface|Drupal\Core\Database\Query\ConditionInterface $conditionsObject
   *   The query object to check.
   * @param \Drupal\Core\Entity\Query\ConditionInterface|Drupal\Core\Database\Query\ConditionInterface $conditionsExpectedObject
   *   The query object with expected conditions.
   * @param bool $onlyListed
   *   Forces to return false, if the checking query object contains more
   *   conditions than in object with expected conditions.
   *
   * @return bool
   *   True if is subset, false if not.
   */
  public static function matchConditions(object $conditionsObject, object $conditionsExpectedObject, $onlyListed = FALSE): bool {
    if ($conditionsObject instanceof EntityQueryConditionInterface) {
      if (strcasecmp($conditionsObject->getConjunction(), $conditionsExpectedObject->getConjunction()) != 0) {
        return FALSE;
      }
      $conditions = $conditionsObject->conditions();
      $conditionsExpected = $conditionsExpectedObject->conditions();
    }
    elseif ($conditionsObject instanceof DatabaseQueryConditionInterface) {
      if (strcasecmp($conditionsObject->conditions()['#conjunction'], $conditionsExpectedObject->conditions()['#conjunction']) != 0) {
        return FALSE;
      }
      $conditions = $conditionsObject->conditions();
      unset($conditions['#conjunction']);
      $conditionsExpected = $conditionsExpectedObject->conditions();
      unset($conditionsExpected['#conjunction']);
    }
    elseif (in_array('Drupal\search_api\Query\ConditionGroupInterface', class_implements($conditionsObject))) {
      if (strcasecmp($conditionsObject->getConjunction(), $conditionsExpectedObject->getConjunction()) != 0) {
        return FALSE;
      }
      $conditions = self::conditionsSearchApiObjectsToArray(self::getProtectedProperty($conditionsObject, 'conditions'));
      $conditionsExpected = self::conditionsSearchApiObjectsToArray(self::getProtectedProperty($conditionsExpectedObject, 'conditions'));
    }
    else {
      throw new \Exception("Conditions should implement Drupal\Core\Entity\Query\ConditionInterface or Drupal\Core\Database\Query\ConditionInterface.");
    }
    $conditionsFound = [];
    foreach ($conditions as $condition) {
      foreach ($conditionsExpected as $conditionsExpectedDelta => $conditionExpected) {
        if (is_object($condition['field']) || is_object($conditionExpected['field'])) {
          if (!is_object($condition['field']) || !is_object($conditionExpected['field'])) {
            continue;
          }
          return self::matchConditions($condition['field'], $conditionExpected['field'], $onlyListed);
        }
        if ($condition == $conditionExpected) {
          if (is_array($condition['value'])) {
            if ($condition['value'] == $conditionExpected['value']) {
              $conditionsFound[$conditionsExpectedDelta] = TRUE;
            }
          }
          else {
            $conditionsFound[$conditionsExpectedDelta] = TRUE;
          }
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
   * Matches a EntityQuery conditon to entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use.
   * @param array $condition
   *   The condition to check.
   *
   * @return bool
   *   True if matches, false if not.
   */
  public static function matchEntityCondition(EntityInterface $entity, array $condition): bool {
    $value = $entity->{$condition['field']} ? $entity->{$condition['field']}->getValue() : NULL;
    switch ($condition['operator']) {
      case 'IN':
        if ($value == NULL && !empty($condition['value'])) {
          return FALSE;
        }
        foreach ($value as $valueItem) {
          if (!in_array($valueItem['value'] ?? NULL, $condition['value'])) {
            return FALSE;
          }
        }
        return TRUE;

      case 'NOT IN':
        if ($value == NULL && !empty($condition['value'])) {
          return TRUE;
        }
        foreach ($value as $valueItem) {
          if (in_array($valueItem['value'], $condition['value'])) {
            return FALSE;
          }
        }
        return TRUE;

      // NULL is treated as `=` condition for EntityQery queries.
      case NULL:
      case '=':
        foreach ($value as $valueItem) {
          if (($valueItem['value'] ?? NULL) == $condition['value']) {
            return TRUE;
          }
        }
        return FALSE;

      // NULL is treated as `=` condition for EntityQery queries.
      case NULL:
      case '=':
        foreach ($value as $valueItem) {
          if (($valueItem['value'] ?? NULL) == $condition['value']) {
            return TRUE;
          }
        }
        return FALSE;

      case 'IS NULL':
        return empty($value);

      case 'IS NOT NULL':
        return !empty($value);

      case '<>':
      case '>':
      case '<':
      case '>=':
      case '<=':
        foreach ($value as $valueItem) {
          // To suppress `The use of function eval() is discouraged` warning.
          // @codingStandardsIgnoreStart
          if (eval("return '" . addslashes($valueItem['value'] ?? NULL) . "' " . $condition['operator'] . " '" . addslashes($condition['value']) . "';")) {
            // @codingStandardsIgnoreEnd
            return TRUE;
          }
        }
        return FALSE;

      default:
        throw new \Exception('A stub for the "' . $condition['operator'] . '" operator is not implemented yet. Please use function stubSetExecuteHandler() to stub the results.');
    }
  }

  /**
   * Performs a check if the actial array is a subset of expected.
   *
   * @param mixed $array
   *   The array to check. Returns false if passed variable is not an array.
   * @param mixed $subset
   *   The array with values to check the subset.
   *
   * @return bool
   *   True if the array is the subset, false if not.
   */
  public static function isNestedArraySubsetOf($array, $subset): bool {
    if ($subset === NULL) {
      return TRUE;
    }
    if (!is_array($array) || !is_array($subset)) {
      return FALSE;
    }
    $result = array_uintersect_assoc($subset, $array, self::class . '::isValueSubsetOfCallback');
    return $result == $subset;
  }

  /**
   * Calls an event subscriber function.
   *
   * @param string $servicesYamlFile
   *   The path to a services.yml file.
   * @param string $serviceName
   *   The name of the service in the services.yml file.
   * @param string $eventName
   *   The Event name.
   * @param mixed $event
   *   The Event object.
   */
  public static function callEventSubscriber(string $servicesYamlFile, string $serviceName, string $eventName, &$event): void {
    $serviceInfo = self::getServiceInfoFromYaml($servicesYamlFile, $serviceName);
    // Checking the presention of 'event_subscriber' tag.
    $tagFound = FALSE;
    foreach ($serviceInfo['tags'] as $tag) {
      if ($tag['name'] == 'event_subscriber') {
        $tagFound = TRUE;
        break;
      }
    }
    if (!$tagFound) {
      throw new \Exception("EventSubscriber $serviceName misses the 'event_subscriber' tag in the service definition");
    }

    $service = self::createServiceFromYaml($servicesYamlFile, $serviceName);
    $subscribedEvents = $service->getSubscribedEvents();
    self::callClassMethods($service, $subscribedEvents[$eventName], [$event]);
  }

  /* ************************************************************************ *
   * Wrappers for UnitTestCase functions to make them available statically.
   * ************************************************************************ */

  /**
   * Gets the random generator for the utility methods.
   *
   * @see \Drupal\Tests\UnitTestCase::getRandomGenerator()
   */
  public static function getRandomGenerator() {
    return UnitTestCaseWrapper::getInstance()->getRandomGenerator();
  }

  /**
   * Sets up a container with a cache tags invalidator.
   *
   * @see \Drupal\Tests\UnitTestCase::getContainerWithCacheTagsInvalidator()
   */
  public static function getContainerWithCacheTagsInvalidator(CacheTagsInvalidatorInterface $cache_tags_validator) {
    return UnitTestCaseWrapper::getInstance()->getContainerWithCacheTagsInvalidator($cache_tags_validator);
  }

  /**
   * Returns a stub class resolver.
   *
   * @see \Drupal\Tests\UnitTestCase::getClassResolverStub()
   */
  public static function getClassResolverStub() {
    return UnitTestCaseWrapper::getInstance()->getClassResolverStub();
  }

  /**
   * Returns a stub translation manager that just returns the passed string.
   *
   * @see \Drupal\Tests\UnitTestCase::getStringTranslationStub()
   */
  public static function getStringTranslationStub() {
    return UnitTestCaseWrapper::getInstance()->getStringTranslationStub();
  }

  /**
   * Returns a mock object for the specified class.
   *
   * @see \Drupal\Tests\UnitTestCase::createMock()
   */
  public static function createMock(string $originalClassName): MockObject {
    return UnitTestCaseWrapper::getInstance()->createMock($originalClassName);
  }

  /**
   * Returns a partial mock object for the specified class.
   *
   * @see \Drupal\Tests\UnitTestCase::createPartialMock()
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

  /**
   * Sets an array as the iterator on a mocked object.
   *
   * @param array $array
   *   The array with data.
   * @param \PHPUnit\Framework\MockObject\MockObject $mock
   *   The mocked object.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked object.
   */
  public static function addIteratorToMock(array $array, MockObject $mock): MockObject {
    $iterator = new \ArrayIterator($array);

    $mock->method('rewind')
      ->willReturnCallback(function () use ($iterator): void {
        $iterator->rewind();
      });

    $mock->method('current')
      ->willReturnCallback(function () use ($iterator) {
        return $iterator->current();
      });

    $mock->method('key')
      ->willReturnCallback(function () use ($iterator) {
        return $iterator->key();
      });

    $mock->method('next')
      ->willReturnCallback(function () use ($iterator): void {
        $iterator->next();
      });

    $mock->method('valid')
      ->willReturnCallback(function () use ($iterator): bool {
        return $iterator->valid();
      });

    $mock->method('offsetGet')
      ->willReturnCallback(function ($key) use ($iterator) {
        return $iterator[$key];
      });

    $mock->method('offsetSet')
      ->willReturnCallback(function ($key, $value) use ($iterator) {
        return $iterator[$key] = $value;
      });

    // @todo Check if the method getIterator is defined and mock it too.
    return $mock;
  }

  /* ************************************************************************ *
   * Internal functions.
   * ************************************************************************ */

  /**
   * Internal callback helper function for array_uintersect.
   */
  private static function isValueSubsetOfCallback($expected, $value): int {
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
  private static function getServiceStubClass(string $serviceName, array $mockableMethods = [], array $addMockableMethods = []): object {
    if (isset(self::SERVICES_CUSTOM_STUBS[$serviceName])) {
      $serviceClass = self::SERVICES_CUSTOM_STUBS[$serviceName];
      if (is_string($serviceClass)) {
        $service = UnitTestCaseWrapper::getInstance()->createPartialMockWithConstructor($serviceClass, $mockableMethods, [], $addMockableMethods);
      }
      elseif (is_array($serviceClass)) {
        $service = call_user_func_array($serviceClass, []);
      }
      else {
        throw new \Exception("Bad format of parameters for $serviceName.");
      }
    }
    else {
      $service = TestHelpers::createServiceMock($serviceName);
    }
    return $service;
  }

  /**
   * Gets a service info from a YAML file.
   */
  private static function getServiceInfoFromYaml(string $servicesYamlFile, string $serviceName): array {
    $services = Yaml::parseFile((str_starts_with($servicesYamlFile, '/') ? '' : DRUPAL_ROOT) . '/' . $servicesYamlFile)['services'];
    return $services[$serviceName];
  }

  /**
   * Converts a condition in Search API format to the associative array.
   */
  private static function conditionsSearchApiObjectsToArray(array $conditionsAsObjects): array {
    foreach ($conditionsAsObjects as $delta => $conditionAsObject) {
      $conditions[$delta] = [
        'field' => $conditionAsObject->getField(),
        'value' => $conditionAsObject->getValue(),
        'operator' => $conditionAsObject->getOperator(),
      ];
    }
    return $conditions;
  }

  /**
   * Gets a service class by name, using Drupal defaults or a custom YAML file.
   */
  private static function getServiceClassByName(string $serviceName, string $servicesYamlFile = NULL): string {
    if ($servicesYamlFile) {
      $services = Yaml::parseFile((str_starts_with($servicesYamlFile, '/') ? '' : DRUPAL_ROOT) . '/' . $servicesYamlFile)['services'];
      $serviceClass = $services[$serviceName]['class'] ?? FALSE;
    }
    else {
      require_once dirname(__FILE__) . '/includes/DrupalCoreServicesMap.data';
      // This trick prevents 'Undefined constant' warnings in code sniffers.
      defined('DRUPAL_CORE_SERVICES_MAP') || define('DRUPAL_CORE_SERVICES_MAP', '');
      $serviceClass = DRUPAL_CORE_SERVICES_MAP[$serviceName] ?? FALSE;
    }
    if (!$serviceClass) {
      throw new \Exception("Service '$serviceName' is missing in the list.");
    }
    return $serviceClass;
  }

  /**
   * Calls class methods from the passed list.
   *
   * A helper function for testing event subscribers.
   *
   * @param object $class
   *   The class to use.
   * @param mixed $methods
   *   The list of methods to call. Can be a string or array, supported formats:
   *   - 'methodName'
   *   - ['methodName', $priority]
   *   - [['methodName1', $priority], ['methodName2']].
   * @param array $arguments
   *   Arguments to pass to the method.
   */
  private static function callClassMethods(object $class, $methods, array $arguments = []) {
    $methodsToCall = [];
    // When a single method is passed as string.
    if (is_string($methods)) {
      $methodsToCall[] = $methods;
    }
    // When a single method is passed as array with function and priority.
    elseif (is_numeric($methods[1] ?? NULL)) {
      $methodsToCall[$methods[1]] = $methods[0];
    }
    // When a list of methids is passed as array.
    else {
      foreach ($methods as $method) {
        if (is_string($method)) {
          $methodsToCall[] = $method;
        }
        elseif (is_array($method)) {
          if (isset($method[1])) {
            $methodsToCall[$method[1]] = $method[0];
          }
          else {
            $methodsToCall[] = $method[0];
          }
        }
      }
    }
    ksort($methodsToCall);
    foreach ($methodsToCall as $method) {
      $class->$method(...$arguments);
    }
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
   * Initializes a new service and adds to the Drupal container, if not exists.
   *
   * @param string $serviceName
   *   The service name.
   * @param object|string|null $class
   *   The class to use in service, allowed different types:
   *   - object: attachs the initialized object to the service.
   *   - string: creates a mock of the class by passed name.
   *   - null: use stub from Test Heleprs of default class from Drupal Core.
   * @param bool $forceOverride
   *   Control overriding the service:
   *   - false: overrides only if the class names are different.
   *   - true: always overrides the class by a new instance.
   * @param array $mockableMethods
   *   The list of exist methods to make mokable.
   * @param array $addMockableMethods
   *   The list of new methods to make them mokable.
   *
   * @return object
   *   The initialised service object.
   *
   * @deprecated in test_helpers:1.0.0-beta4 and is removed from
   *   test_helpers:1.0.0-rc1. Use TestHelpers::service().
   * @see https://www.drupal.org/project/test_helpers/issues/3336364
   */
  public static function addService(string $serviceName, $class = NULL, bool $forceOverride = FALSE, array $mockableMethods = [], array $addMockableMethods = []): object {
    @trigger_error('Function addService() is deprecated in test_helpers:1.0.0-beta4 and is removed from test_helpers:1.0.0-rc1. Renamed to service(). See https://www.drupal.org/project/test_helpers/issues/3336364', E_USER_DEPRECATED);
    return self::service($serviceName, $class, $forceOverride, $mockableMethods, $addMockableMethods);
  }

  /**
   * Initializes list of services and adds them to the container.
   *
   * @param array $services
   *   The array with services, supports two formats:
   *   - non associative array with service names: adds default classes.
   *   - service name as key and object or null in value: adds the class to the
   *     service, use NULL to add default class.
   * @param bool $clearContainer
   *   Clears the Drupal container, if true.
   *
   * @deprecated in test_helpers:1.0.0-beta4 and is removed from
   *   test_helpers:1.0.0-rc1. Use TestHelpers::service().
   * @see https://www.drupal.org/project/test_helpers/issues/3336364
   */
  public static function addServices(array $services, bool $clearContainer = FALSE): void {
    @trigger_error('Function addServices() is deprecated in test_helpers:1.0.0-beta4 and is removed from test_helpers:1.0.0-rc1. Renamed to setServices(). See https://www.drupal.org/project/test_helpers/issues/3336364', E_USER_DEPRECATED);
    self::setServices($services, $clearContainer);
  }

  /**
   * Binds a closure function to a mocked class method.
   *
   * This makes accessible the class methods inside the function via $this.
   *
   * @param \Closure $closure
   *   The closure function to bind.
   * @param \PHPUnit\Framework\MockObject\MockObject $class
   *   The mocked class.
   * @param string $method
   *   The method name.
   *
   * @deprecated in test_helpers:1.0.0-beta4 and is removed from
   *   test_helpers:1.0.0-rc1. Use TestHelpers::service().
   * @see https://www.drupal.org/project/test_helpers/issues/3336364
   */
  public static function bindClosureToClassMethod(\Closure $closure, MockObject $class, string $method): void {
    @trigger_error('Function bindClosureToClassMethod() is deprecated in test_helpers:1.0.0-beta4 and is removed from test_helpers:1.0.0-rc1. Renamed to setClassMethod() with changing the order of the arguments. See https://www.drupal.org/project/test_helpers/issues/3336574', E_USER_DEPRECATED);
    self::setMockedClassMethod($class, $method, $closure);
  }

  /**
   * Creates a service via calling function create() with container.
   *
   * Tests the correct work of create() and __construct() functions
   * and does the assertion of class match.
   *
   * @param string|object $class
   *   The class to test, can be a string with path or initialized class.
   * @param array $createArguments
   *   The list of arguments for passing to function create().
   * @param array $services
   *   The array of services to add to the container.
   *   Format is same as in function setServices().
   *
   * @return object
   *   The initialized class instance.
   *
   * @deprecated in test_helpers:1.0.0-beta4 and is removed from
   *   test_helpers:1.0.0-rc1. Use TestHelpers::service().
   * @see https://www.drupal.org/project/test_helpers/issues/3336364
   */
  public static function createService($class, array $createArguments = NULL, array $services = NULL): object {
    @trigger_error('Function createService() is deprecated in test_helpers:1.0.0-beta4 and is removed from test_helpers:1.0.0-rc1. Renamed to createClass(). See https://www.drupal.org/project/test_helpers/issues/3336801', E_USER_DEPRECATED);
    return self::createClass($class, $createArguments, $services);
  }

  /**
   * Creates a stub entity for an entity type from a given class.
   *
   * @param string $entityTypeClassName
   *   The entity type class.
   * @param array $values
   *   An array with entity values:
   *   - keys: field/property names.
   *   - values: the field/property values.
   * @param array $options
   *   The array of options:
   *   - entity_base_type: base type of the entity:
   *     ContentEntityType or ConfigEntityType, default is ContentEntityType.
   *   - @see \Drupal\test_helpers\StubFactory\EntityStubFactory::create()
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\test_helpers\StubFactory\EntityStubInterface
   *   The stub object for the entity.
   *
   * @deprecated in test_helpers:1.0.0-beta5 and is removed from
   *   test_helpers:1.0.0-rc1. Renamed to createEntity().
   * @see https://www.drupal.org/project/test_helpers/issues/3337449
   */
  public static function createEntityStub(string $entityTypeClassName, array $values = [], array $options = []) {
    @trigger_error('Function createEntityStub() is deprecated in test_helpers:1.0.0-beta5 and is removed from test_helpers:1.0.0-rc1. Renamed to createEntity(). See https://www.drupal.org/project/test_helpers/issues/3337449', E_USER_DEPRECATED);
    return self::createEntity($entityTypeClassName, $values, $options);
  }

  /**
   * Creates a stub entity for an entity type from a given class and saves it.
   *
   * @param string $entityTypeClassName
   *   The entity type class.
   * @param array $values
   *   An array with entity values:
   *   - keys: field/property names.
   *   - values: the field/property values.
   * @param array $options
   *   The array of options:
   *   - entity_base_type: base type of the entity:
   *     ContentEntityType or ConfigEntityType, default is ContentEntityType.
   *   - @see \Drupal\test_helpers\StubFactory\EntityStubFactory::create()
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\test_helpers\StubFactory\EntityStubInterface
   *   The stub object for the entity.
   *
   * @deprecated in test_helpers:1.0.0-beta5 and is removed from
   *   test_helpers:1.0.0-rc1. Renamed to saveEntity().
   * @see https://www.drupal.org/project/test_helpers/issues/3337449
   */
  public static function saveEntityStub(string $entityTypeClassName, array $values = [], array $options = []) {
    @trigger_error('Function saveEntityStub() is deprecated in test_helpers:1.0.0-beta5 and is removed from test_helpers:1.0.0-rc1. Renamed to saveEntity(). See https://www.drupal.org/project/test_helpers/issues/3337449', E_USER_DEPRECATED);
    return self::saveEntity($entityTypeClassName, $values, $options);
  }

  /**
   * Gets or initializes an Entity Storage for a given Entity Type class name.
   *
   * @param string $entityTypeClassName
   *   The entity class.
   * @param string $annotation
   *   The annotation class.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The initialized stub of Entity Storage.
   *
   * @deprecated in test_helpers:1.0.0-beta5 and is removed from
   *   test_helpers:1.0.0-rc1. Renamed to getEntityStorage().
   * @see https://www.drupal.org/project/test_helpers/issues/3337449
   */
  public static function getEntityStorageStub(string $entityTypeClassName, string $annotation = NULL): EntityStorageInterface {
    @trigger_error('Function getEntityStorageStub() is deprecated in test_helpers:1.0.0-beta5 and is removed from test_helpers:1.0.0-rc1. Renamed to getEntityStorage(). See https://www.drupal.org/project/test_helpers/issues/3337449', E_USER_DEPRECATED);
    return self::getEntityStorage($entityTypeClassName, $annotation);
  }

}