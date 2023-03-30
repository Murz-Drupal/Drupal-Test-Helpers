<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Query\ConditionInterface as DatabaseQueryConditionInterface;
use Drupal\Core\Database\Query\SelectInterface as DatabaseSelectInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityBundleListenerStub;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\ConditionInterface as EntityQueryConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface as EntityQueryInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\test_helpers\Stub\CacheContextsManagerStub;
use Drupal\test_helpers\Stub\ConfigFactoryStub;
use Drupal\test_helpers\Stub\ConfigurableLanguageManagerStub;
use Drupal\test_helpers\Stub\DatabaseStub;
use Drupal\test_helpers\Stub\DateFormatterStub;
use Drupal\test_helpers\Stub\EntityFieldManagerStub;
use Drupal\test_helpers\Stub\EntityRepositoryStub;
use Drupal\test_helpers\Stub\EntityTypeBundleInfoStub;
use Drupal\test_helpers\Stub\EntityTypeManagerStub;
use Drupal\test_helpers\Stub\LoggerChannelFactoryStub;
use Drupal\test_helpers\Stub\MessengerStub;
use Drupal\test_helpers\Stub\ModuleHandlerStub;
use Drupal\test_helpers\Stub\TokenStub;
use Drupal\test_helpers\Stub\TypedDataManagerStub;
use Drupal\test_helpers\StubFactory\EntityStubFactory;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MethodNameNotConfiguredException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

// Some constants are required to be defined for calling Drupal API functions
// from 'core/modules/system/system.module' file.
// - DRUPAL_DISABLED
// - DRUPAL_OPTIONAL
// - DRUPAL_REQUIRED
// - REGIONS_VISIBLE
// - REGIONS_ALL
// Redefining them here to not include the whole file.
defined('DRUPAL_DISABLED') || define('DRUPAL_DISABLED', 0);
defined('DRUPAL_OPTIONAL') || define('DRUPAL_OPTIONAL', 1);
defined('DRUPAL_REQUIRED') || define('DRUPAL_REQUIRED', 2);
defined('REGIONS_VISIBLE') || define('REGIONS_VISIBLE', 'visible');
defined('REGIONS_ALL') || define('REGIONS_ALL', 'all');

// And some more constants from '/core/includes/common.inc' file.
// - SAVED_NEW
// - SAVED_UPDATED
// Redefining them here to not include the whole file.
defined('SAVED_NEW') || define('SAVED_NEW', 1);
defined('SAVED_UPDATED') || define('SAVED_UPDATED', 2);

/**
 * Helper functions to simplify writing of Unit Tests.
 */
class TestHelpers {

  /**
   * An array of implemented custom stubs for services.
   *
   * Key: a service name.
   * Value: a service class, or a callback function to initialize an instance
   * as array in format "[className, functionName]".
   */
  public const SERVICES_CUSTOM_STUBS = [
    'test_helpers.keyvalue.memory' => KeyValueMemoryFactory::class,
    'cache_contexts_manager' => CacheContextsManagerStub::class,
    'class_resolver' => [self::class, 'getClassResolverStub'],
    'config.factory' => ConfigFactoryStub::class,
    'database' => DatabaseStub::class,
    'date.formatter' => DateFormatterStub::class,
    'entity_bundle.listener' => EntityBundleListenerStub::class,
    'entity_field.manager' => EntityFieldManagerStub::class,
    'entity_type.bundle.info' => EntityTypeBundleInfoStub::class,
    'entity_type.manager' => EntityTypeManagerStub::class,
    'entity.repository' => EntityRepositoryStub::class,
    'language_manager' => ConfigurableLanguageManagerStub::class,
    'logger.factory' => LoggerChannelFactoryStub::class,
    'messenger' => MessengerStub::class,
    'module_handler' => ModuleHandlerStub::class,
    'string_translation' => [self::class, 'getStringTranslationStub'],
    'token' => TokenStub::class,
    'typed_data_manager' => TypedDataManagerStub::class,
  ];

  /**
   * A list of core services that can be initialized automatically.
   */
  public const SERVICES_CORE_INIT = [
    'cache.backend.memory',
    'cache_tags.invalidator',
    'datetime.time',
    'entity.memory_cache',
    'request_stack',
    'transliteration',
    'uuid',
  ];

  /**
   * Gets a private or protected method from a class using reflection.
   *
   * @param object|string $class
   *   The class instance or the name of the class.
   * @param string $methodName
   *   The name of the method to get.
   *
   * @return \ReflectionMethod
   *   The method instance.
   */
  public static function getPrivateMethod($class, string $methodName): \ReflectionMethod {
    $reflection = new \ReflectionClass($class);
    $method = $reflection
      ->getMethod($methodName);
    $method
      ->setAccessible(TRUE);
    return $method;
  }

  /**
   * Calls a private or protected method from a class using reflection.
   *
   * @param object|string $class
   *   The class instance or the name of the class.
   * @param string $methodName
   *   The name of the method to get.
   * @param array $arguments
   *   The list of aruments for the calling method.
   *
   * @return mixed
   *   The return value of the executed function.
   */
  public static function callPrivateMethod($class, string $methodName, array $arguments = []) {
    $method = self::getPrivateMethod($class, $methodName);
    return $method->invokeArgs(is_object($class) ? $class : NULL, $arguments);
  }

  /**
   * Gets a private or protected property from a class using reflection.
   *
   * @param object|string $class
   *   The class instance or the name of the class.
   * @param string $propertyName
   *   The name of the property to get.
   * @param bool $returnReflectionProperty
   *   Flag to return a ReflectionProperty object instead of value.
   *
   * @return mixed
   *   The property value.
   */
  public static function getPrivateProperty($class, string $propertyName, $returnReflectionProperty = FALSE) {
    $reflection = new \ReflectionClass($class);
    if (is_string($class)) {
      // Considering property as a static.
      return $reflection->getStaticPropertyValue($propertyName);
    }
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
   * Sets a private or protected property value in a class using reflection.
   *
   * @param object|string $class
   *   The class instance or the name of the class.
   * @param string $propertyName
   *   The name of the property to get.
   * @param mixed $value
   *   The value to set.
   */
  public static function setPrivateProperty($class, string $propertyName, $value): void {
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
    $configurableMethods = self::getPrivateProperty($invocationHandler, 'configurableMethods');
    $matchers = self::getPrivateProperty($invocationHandler, 'matchers');
    foreach ($matchers as $matcher) {
      $methodNameRuleObject = self::getPrivateProperty($matcher, 'methodNameRule');
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
   * Gets a path to file for the class.
   *
   * @param mixed $class
   *   Class name or path.
   *
   * @return string
   *   The path to the file.
   */
  public static function getClassFile($class) {
    $reflection = new \ReflectionClass($class);
    return $reflection->getFileName() ?? NULL;
  }

  /**
   * Finds a Drupal root directory.
   *
   * @return string
   *   A path to the Drupal root directory.
   */
  public static function getDrupalRoot(): string {
    static $path;
    if ($path) {
      return $path;
    }
    $path = __DIR__;
    while (!file_exists($path . '/core/lib/Drupal.php')) {
      $path = dirname($path);
      if ($path == '') {
        throw new \Exception('Drupal root directory cannot be found.');
      }
    }
    return $path;
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

      // $definitions[$plugin][$class] = $definition;
      return $definition;
    }
    else {
      // Throw new \Exception('Definition not found in annotation.');.
      return FALSE;
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
   * Initializes a service from YAML file with passing services as arguments.
   *
   * @param string|array $servicesYamlFileOrData
   *   The path to the YAML file, or an array with data from YAML.
   * @param string $name
   *   The name of the service.
   * @param array|null $mockMethods
   *   A list of method to mock when creating the instance.
   *
   * @return object
   *   The initialized class instance.
   */
  public static function initServiceFromYaml($servicesYamlFileOrData, string $name, array $mockMethods = NULL): object {
    if (is_string($servicesYamlFileOrData)) {
      $serviceInfo = self::getServiceInfoFromYaml($name, $servicesYamlFileOrData);
    }
    elseif (is_array($servicesYamlFileOrData)) {
      $serviceInfo = $servicesYamlFileOrData;
    }
    else {
      throw new \Error('The first argument should be a path to a YAML file, or array with data.');
    }
    $classArguments = [];
    foreach (($serviceInfo['arguments'] ?? []) as $argument) {
      if (substr($argument, 0, 1) == '@') {
        $classArguments[] = self::service(substr($argument, 1));
      }
      else {
        $classArguments[] = $argument;
      }
    }
    if ($mockMethods) {
      $classInstance = TestHelpers::createPartialMockWithConstructor(
        $serviceInfo['class'],
        $mockMethods,
        $classArguments,
      );
    }
    else {
      $classInstance = new $serviceInfo['class'](...$classArguments);
    }
    if (method_exists($classInstance, 'setContainer')) {
      $classInstance->setContainer(self::getContainer());
    }
    return $classInstance;
  }

  /**
   * Initializes a service from the service name or class.
   *
   * The function tries to auto detect the service YAML file location
   * automatically by service name or class name.  If auto magic doesn't work
   * for your case, use the initServiceFromYaml() directly.
   *
   * @param string $serviceNameOrClass
   *   The name of the service id in YAML file of the current module,
   *   or the full name of a service class.
   * @param string $serviceNameToCheck
   *   A service name to check matching the declared one in services.yml file.
   *   Acts only if the class name is passed as a first argument.
   * @param array|null $mockMethods
   *   A list of method to mock when creating the instance.
   *
   * @return object
   *   The initialized class instance.
   */
  public static function initService(
    string $serviceNameOrClass,
    string $serviceNameToCheck = NULL,
    array $mockMethods = NULL
  ): object {
    if (strpos($serviceNameOrClass, '\\') === FALSE) {
      $serviceName = $serviceNameOrClass;
      self::requireCoreFeaturesMap();
      if (isset(TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP[$serviceName])) {
        return self::initServiceFromYaml(TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP[$serviceName], $serviceName, $mockMethods);
      }
      else {
        // We have a service id name, use the current module as the module name.
        $callerInfo = self::getCallerInfo();
        $moduleName = self::getModuleName($callerInfo['class']);
        $moduleRoot = self::getModuleRoot($callerInfo['file'], $moduleName);
        $servicesFile = "$moduleRoot/$moduleName.services.yml";
      }
    }
    else {
      $serviceClass = ltrim($serviceNameOrClass, '\\');
      $serviceInfo = self::getServiceInfoFromClass($serviceClass);
      $serviceName = $serviceInfo['#name'];
      $servicesFile = $serviceInfo['#file'];
      if (!isset($serviceName)) {
        throw new \Exception("Can't find the service name by class $serviceClass.");
      }
      if ($serviceNameToCheck && $serviceNameToCheck !== $serviceName) {
        throw new \Exception("The service name '$serviceName' differs from required name '$serviceNameToCheck'");
      }
    }
    return self::initServiceFromYaml($servicesFile, $serviceName, $mockMethods);
  }

  /**
   * Gets information about service from YAML file.
   *
   * @param string $serviceClass
   *   A class to search.
   *
   * @return array|null
   *   An array with information, or NULL if nothing found.
   */
  public static function getServiceInfoFromClass(string $serviceClass): ?array {
    $serviceClass = ltrim($serviceClass, '\\');
    $moduleName = self::getModuleName($serviceClass);
    $reflection = new \ReflectionClass($serviceClass);
    $fileName = $reflection->getFileName();
    $moduleRoot = self::getModuleRoot($fileName, $moduleName);
    $servicesFile = "$moduleRoot/$moduleName.services.yml";
    try {
      $servicesFileData = self::parseYamlFile($servicesFile);
    }
    catch (\Exception $e) {
      return NULL;
    }
    foreach ($servicesFileData['services'] as $name => $info) {
      if (($info['class'] ?? NULL) == $serviceClass) {
        $info['#name'] = $name;
        $info['#file'] = $servicesFile;
        return $info;
      }
    }
    return NULL;
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
    if ($forceCreate || !\Drupal::hasContainer()) {
      $container = new ContainerBuilder();
      // Setting default parameters, required for some Core services.
      $container->setParameter('cache_bins', []);
      \Drupal::setContainer($container);
    }
    return \Drupal::getContainer();
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
   *   - FALSE on NULL: overrides only if the class names are different.
   *   - TRUE: always overrides the class by a new instance.
   * @param array $mockableMethods
   *   The list of exist methods to make mokable.
   * @param array $addMockableMethods
   *   The list of new methods to make them mokable.
   * @param bool $initService
   *   Initializes core service with constructor and passing all dependencies.
   *
   * @return object
   *   The initialised service object.
   */
  public static function service(
    string $serviceName,
    $class = NULL,
    bool $forceOverride = NULL,
    array $mockableMethods = NULL,
    array $addMockableMethods = NULL,
    bool $initService = NULL
  ): object {
    $container = self::getContainer();
    if ($container->has($serviceName) && $class === NULL && !$forceOverride) {
      return $container->get($serviceName);
    }
    $service = NULL;
    if ($class === NULL) {
      if ($initService !== FALSE) {
        $service = self::getServiceStubClass($serviceName, $mockableMethods, $addMockableMethods);
      }
    }
    elseif (is_string($class)) {
      if ($initService) {
        $service = self::initService($class);
      }
      else {
        $service = self::createMock($class);
      }
    }
    elseif (is_object($class)) {
      $service = $class;
    }
    else {
      throw new \Exception("Class should be an object, string as path to class, or NULL.");
    }

    // If we still have not initialized service, use core services list.
    if ($service === NULL) {
      $coreServiceClass = self::getServiceClassByName($serviceName);
      if (
        $initService
        // In case when we need to return a mock of an auto initalized service.
        || ($initService !== FALSE && in_array($serviceName, self::SERVICES_CORE_INIT))
      ) {
        if (self::getModuleName($coreServiceClass)) {
          $service = self::initService($coreServiceClass, NULL, $mockableMethods);
        }
        else {
          // In case of Symfony service, it has no Drupal dependencies.
          if (empty($mockableMethods) && empty($addMockableMethods)) {
            $service = new $coreServiceClass();
          }
          else {
            $service = self::initService($coreServiceClass, NULL, $mockableMethods);
          }
        }
      }
      else {
        $service = self::createMock($coreServiceClass);
      }
    }

    if ($container->has($serviceName)) {
      $configuredService = $container->get($serviceName);

      // Checking if service of already defined and has the same class as
      // the passed service.
      if (
        (get_class($configuredService) !== get_class($service))
        || $forceOverride
      ) {
        $container->set($serviceName, $service);
        return $service;
      }

      return $configuredService;
    }
    else {
      $container->set($serviceName, $service);
      return $service;
    }
  }

  /**
   * Initializes list of services and adds them to the container.
   *
   * @param array $services
   *   An array with services, supports two formats:
   *   - A numeric array with service names: adds default classes.
   *   - An associative array with service name as a key and object or NULL
   *     in value: Attaches the passed class to the service, if NULL - creates
   *     a stub for default Drupal Core class.
   * @param bool $clearContainer
   *   Clears the Drupal container, if TRUE.
   * @param bool $forceOverride
   *   Control overriding the service:
   *   - FALSE on NULL: overrides only if the class names are different.
   *   - TRUE: always overrides the class by a new instance.
   * @param bool $initServices
   *   Initializes core service with constructor and passing all dependencies.
   */
  public static function setServices(
    array $services,
    bool $clearContainer = NULL,
    bool $forceOverride = NULL,
    bool $initServices = NULL
  ): void {
    if ($clearContainer) {
      TestHelpers::getContainer(TRUE);
    }
    foreach ($services as $key => $value) {
      if (is_int($key)) {
        // If we have only a service name - just reuse the default behavior.
        self::service($value);
      }
      else {
        // If we have a service name in key and class in value - pass the class.
        self::service($key, $value);
      }
    }
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
  public static function getServiceStub(string $serviceName, array $mockableMethods = NULL, array $addMockableMethods = NULL): object {
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
   * @param string $entityTypeNameOrClass
   *   A full path to an entity type class, or an entity type id for Drupal
   *   Core entities like `node`, `taxonomy_term`, etc.
   * @param array $values
   *   A list of values to set in the created entity.
   * @param array $translations
   *   A list of translations to add to the created entity.
   * @param array $options
   *   A list of options to entity stub creation:
   *   - mockMethods: list of methods to make mockable.
   *   - addMethods: list of additional methods.
   *   - skipPrePostSave: a flag to use direct save on the storage without
   *     calling preSave and postSave functions. Can be useful if that functions
   *     have dependencies which hard to mock. Applies only on the first
   *     initialization of this node type.
   *   - skipEntityConstructor: a flag to skip calling the entity constructor.
   *   - fields: a list of custom field options by field name.
   *     Applies only on the first initialization of this field.
   *     Field options supportable formats:
   *     - A string, indicating field type, like 'integer', 'string',
   *       'entity_reference', only core field types are supported.
   *     - An array with field type and settings, like this:
   *       [
   *        '#type' => 'entity_reference',
   *        '#settings' => ['target_type' => 'node']
   *       ].
   *     - A field definition object, that will be applied to the field.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\test_helpers\StubFactory\EntityStubInterface
   *   The stub object for the entity.
   */
  public static function createEntity(string $entityTypeNameOrClass, array $values = NULL, array $translations = NULL, array $options = NULL) {
    $options ??= [];
    // Splitting $options to entity options and storage options.
    if (isset($options['skipPrePostSave'])) {
      $storageOptions['skipPrePostSave'] = $options['skipPrePostSave'];
      unset($options['skipPrePostSave']);
    }
    return EntityStubFactory::create($entityTypeNameOrClass, $values, $translations, $options, $storageOptions ?? NULL);
  }

  /**
   * Creates a stub entity for an entity type from a given class and saves it.
   *
   * @param string $entityTypeNameOrClass
   *   A full path to an entity type class, or an entity type id for Drupal
   *   Core entities like `node`, `taxonomy_term`, etc.
   * @param array $values
   *   A list of values to set in the created entity.
   * @param array $translations
   *   A list of translations to add to the created entity.
   * @param array $options
   *   A list of options to entity stub creation:
   *   - mockMethods: list of methods to make mockable.
   *   - addMethods: list of additional methods.
   *   - skipPrePostSave: a flag to use direct save on the storage without
   *     calling preSave and postSave functions. Can be useful if that functions
   *     have dependencies which hard to mock. Applies only on the first
   *     initialization of this node type.
   *   - skipEntityConstructor: a flag to skip calling the entity constructor.
   *   - fields: a list of custom field options by field name.
   *     Applies only on the first initialization of this field.
   *     Field options supportable formats:
   *     - A string, indicating field type, like 'integer', 'string',
   *       'entity_reference', only core field types are supported.
   *     - An array with field type and settings, like this:
   *       [
   *        '#type' => 'entity_reference',
   *        '#settings' => ['target_type' => 'node']
   *       ].
   *     - A field definition object, that will be applied to the field.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\test_helpers\StubFactory\EntityStubInterface
   *   The stub object for the entity.
   */
  public static function saveEntity(string $entityTypeNameOrClass, array $values = NULL, array $translations = NULL, array $options = NULL) {
    $entity = self::createEntity($entityTypeNameOrClass, $values, $translations, $options);
    $entity->save();
    return $entity;
  }

  /**
   * Gets or initializes an Entity Storage for a given Entity Type class name.
   *
   * @param string $entityTypeNameOrClass
   *   The entity class.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storageInstance
   *   An already initialized instance of a storage, NULL to create a new one.
   * @param bool $forceOverride
   *   Forces creation of the new clear storage, if exists.
   * @param array $storageOptions
   *   A list of options to pass to the storage initialization. Acts only once
   *   if the storage is not initialized yet.
   *   - skipPrePostSave: a flag to use direct save on the storage without
   *     calling preSave and postSave functions. Can be useful if that functions
   *     have dependencies which hard to mock.
   *   - constructorArguments: additional arguments to the constructor.
   *   - mockMethods: a list of storage methods to mock.
   *   - addMethods: a list of storage methods to add.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The initialized stub of Entity Storage.
   */
  public static function getEntityStorage(string $entityTypeNameOrClass, EntityStorageInterface $storageInstance = NULL, bool $forceOverride = FALSE, array $storageOptions = NULL): EntityStorageInterface {
    return self::getServiceStub('entity_type.manager')->stubGetOrCreateStorage($entityTypeNameOrClass, $storageInstance, $forceOverride, $storageOptions);
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
   * @param \Drupal\Core\Entity\Query\ConditionInterface|\Drupal\Core\Database\Query\ConditionInterface $query
   *   The query object to check.
   * @param \Drupal\Core\Entity\Query\ConditionInterface|\Drupal\Core\Database\Query\ConditionInterface $queryExpected
   *   The query object with expected conditions.
   * @param bool $onlyListed
   *   Forces to return false, if the checking query object contains more
   *   conditions than in object with expected conditions.
   * @param bool $throwErrors
   *   Enables throwing notice errors when matching fails, with the explanation
   *   what exactly doesn't match.
   *
   * @return bool
   *   True if is subset, false if not.
   */
  public static function queryIsSubsetOf(object $query, object $queryExpected, bool $onlyListed = FALSE, bool $throwErrors = TRUE): bool {
    if ($query instanceof DatabaseSelectInterface && $queryExpected instanceof DatabaseSelectInterface) {
      $order = self::getPrivateProperty($query, 'order');
      $orderExpected = self::getPrivateProperty($queryExpected, 'order');
      if (!self::isNestedArraySubsetOf($order, $orderExpected)) {
        $throwErrors && self::throwMatchError('order', $orderExpected, $order);
        return FALSE;
      }

    }
    elseif ($query instanceof EntityQueryInterface && $queryExpected instanceof EntityQueryInterface) {
      if ($query->getEntityTypeId() != $queryExpected->getEntityTypeId()) {
        $throwErrors && self::throwMatchError('entity type', $queryExpected->getEntityTypeId(), $query->getEntityTypeId());
        return FALSE;
      }
      $sort = self::getPrivateProperty($query, 'sort');
      $sortExpected = self::getPrivateProperty($queryExpected, 'sort');
      if (!self::isNestedArraySubsetOf($sort, $sortExpected)) {
        $throwErrors && self::throwMatchError('sort', $sortExpected, $sort);
        return FALSE;
      }
      if (is_bool($accessCheckExpected = self::getPrivateProperty($queryExpected, 'accessCheck'))) {
        if ($accessCheckExpected !== $accessCheckActual = self::getPrivateProperty($query, 'accessCheck')) {
          $throwErrors && self::throwMatchError('accessCheck', $accessCheckExpected, $accessCheckActual);
          return FALSE;
        }
      }

    }
    else {
      throw new \Exception('Unsupportable query types.');
    }
    $range = self::getPrivateProperty($query, 'range');
    $rangeExpected = self::getPrivateProperty($queryExpected, 'range');
    if (!self::isNestedArraySubsetOf($range, $rangeExpected)) {
      $throwErrors && self::throwMatchError('range', $rangeExpected, $range);
      return FALSE;
    }

    $conditions = self::getPrivateProperty($query, 'condition');
    $conditionsExpected = self::getPrivateProperty($queryExpected, 'condition');
    if (!self::matchConditions($conditions, $conditionsExpected, $onlyListed, $throwErrors)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Searches query conditions by a field name or sub-conditions.
   *
   * @param object $query
   *   The query object.
   * @param string|array $requiredCondition
   *   The string with the field name to search.
   *   Or an array with sub-conditions like
   *   `['field' => 'nid', 'operator' => '<>']`.
   * @param bool $returnAllMatches
   *   A flag to return all matches as a list, not only the first match.
   *
   * @return array|null
   *   The first matched condition, or NULL if no matcheds.
   */
  public static function findQueryCondition(object $query, $requiredCondition, bool $returnAllMatches = FALSE): ?array {
    $conditionsProperty = self::getPrivateProperty($query, 'condition');
    $conditions = $conditionsProperty->conditions();
    $matches = [];
    foreach ($conditions as $condition) {
      if (
        (is_string($requiredCondition) && ($condition['field'] ?? NULL) == $requiredCondition)
        || (is_array($requiredCondition) && self::isNestedArraySubsetOf($condition, $requiredCondition))
      ) {
        if ($returnAllMatches) {
          $matches[] = $condition;
        }
        else {
          return $condition;
        }
      }
    }
    if ($returnAllMatches && $matches) {
      return $matches;
    }
    return NULL;
  }

  /**
   * Performs matching of passed conditions with the query.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface|\Drupal\Core\Database\Query\ConditionInterface $conditionsObject
   *   The query object to check.
   * @param \Drupal\Core\Entity\Query\ConditionInterface|\Drupal\Core\Database\Query\ConditionInterface $conditionsExpectedObject
   *   The query object with expected conditions.
   * @param bool $onlyListed
   *   Forces to return false, if the checking query object contains more
   *   conditions than in object with expected conditions.
   * @param bool $throwErrors
   *   Enables throwing notice errors when matching fails, with the explanation
   *   what exactly doesn't match.
   *
   * @return bool
   *   True if is subset, false if not.
   */
  public static function matchConditions(object $conditionsObject, object $conditionsExpectedObject, bool $onlyListed = FALSE, bool $throwErrors = FALSE): bool {
    if ($conditionsObject instanceof EntityQueryConditionInterface) {
      if (strcasecmp($conditionsObject->getConjunction(), $conditionsExpectedObject->getConjunction()) != 0) {
        $throwErrors && self::throwMatchError('conjunction', $conditionsObject->getConjunction(), $conditionsExpectedObject->getConjunction());
        return FALSE;
      }
      $conditions = $conditionsObject->conditions();
      $conditionsExpected = $conditionsExpectedObject->conditions();
    }
    elseif ($conditionsObject instanceof DatabaseQueryConditionInterface) {
      if (strcasecmp($conditionsObject->conditions()['#conjunction'], $conditionsExpectedObject->conditions()['#conjunction']) != 0) {
        $throwErrors && self::throwMatchError('conjunction', $conditionsExpectedObject->conditions()['#conjunction'], $conditionsObject->conditions()['#conjunction']);
        return FALSE;
      }
      $conditions = $conditionsObject->conditions();
      unset($conditions['#conjunction']);
      $conditionsExpected = $conditionsExpectedObject->conditions();
      unset($conditionsExpected['#conjunction']);
    }
    elseif (in_array('Drupal\search_api\Query\ConditionGroupInterface', class_implements($conditionsObject))) {
      if (strcasecmp($conditionsObject->getConjunction(), $conditionsExpectedObject->getConjunction()) != 0) {
        $throwErrors && self::throwMatchError('conjunction', $conditionsExpectedObject->getConjunction(), $conditionsObject->getConjunction());
        return FALSE;
      }
      $conditions = self::conditionsSearchApiObjectsToArray(self::getPrivateProperty($conditionsObject, 'conditions'));
      $conditionsExpected = self::conditionsSearchApiObjectsToArray(self::getPrivateProperty($conditionsExpectedObject, 'conditions'));
    }
    else {
      throw new \Exception("Conditions should implement Drupal\Core\Entity\Query\ConditionInterface or Drupal\Core\Database\Query\ConditionInterface.");
    }
    $conditionsFound = [];
    $conditionsExpectedFound = [];
    foreach ($conditions as $conditionDelta => $condition) {
      foreach ($conditionsExpected as $conditionsExpectedDelta => $conditionExpected) {
        if (is_object($condition['field']) || is_object($conditionExpected['field'])) {
          if (!is_object($condition['field']) || !is_object($conditionExpected['field'])) {
            continue;
          }
          $conditionGroupMatchResult = self::matchConditions($condition['field'], $conditionExpected['field'], $onlyListed, FALSE);
          if ($conditionGroupMatchResult === TRUE) {
            $conditionsExpectedFound[$conditionsExpectedDelta] = TRUE;
            $conditionsFound[$conditionDelta] = TRUE;
          }
        }
        elseif ($condition == $conditionExpected) {
          if (is_array($condition['value'])) {
            if ($condition['value'] == $conditionExpected['value']) {
              $conditionsExpectedFound[$conditionsExpectedDelta] = TRUE;
              $conditionsFound[$conditionDelta] = TRUE;
            }
          }
          else {
            $conditionsExpectedFound[$conditionsExpectedDelta] = TRUE;
            $conditionsFound[$conditionDelta] = TRUE;
          }
        }
      }
    }
    if (count($conditionsExpectedFound) != count($conditionsExpected)) {
      foreach ($conditionsExpected as $delta => $condition) {
        if (!isset($conditionsExpectedFound[$delta])) {
          // Happens when condition is a conditionGroup.
          if (is_object($condition['field'])) {
            $groupConditions = [];
            foreach ($condition['field']->conditions() as $groupCondition) {
              if (is_object($groupCondition['field'])) {
                // @todo Try to find the deep failing condition.
                $groupCondition['field'] = '[' . $groupCondition['field']->getConjunction() . 'ConditionGroup with ' . count($groupCondition['field']->conditions()) . ' items]';
              }
              $groupConditions[] = $groupCondition;
            }
            $throwErrors && self::throwUserError('The expected condition group "' . $condition['field']->getConjunction() . '" is not matching, items: ' . var_export($groupConditions, TRUE));
            return FALSE;
          }
          $throwErrors && self::throwUserError('The expected condition is not found: ' . var_export($condition, TRUE));
          return FALSE;
        }
      }
      $throwErrors && self::throwMatchError('count of matched conditions', count($conditionsExpected), count($conditionsExpectedFound));
      return FALSE;
    }
    if ($onlyListed && (count($conditions) != count($conditionsExpected))) {
      foreach ($conditions as $delta => $condition) {
        if (!isset($conditionsFound[$delta])) {
          $throwErrors && self::throwUserError('The condition is not listed in expected: ' . var_export($condition, TRUE));
        }
      }
      $throwErrors && self::throwMatchError('count of conditions', count($conditions), count($conditionsExpectedFound));
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
    $exceptionSuffix = ' Please use function stubSetExecuteHandler() to stub the results.';
    if (strpos($condition['field'], '.')) {
      $parts = explode('.', $condition['field']);
      if (count($parts) > 2) {
        throw new \Error('Function does not support deep references in fields yet, only field property name is supported.' . $exceptionSuffix);
      }
      $fieldName = $parts[0];
      $propertyName = $parts[1];
    }
    else {
      $fieldName = $condition['field'];
    }
    $field = $entity->$fieldName;
    $fieldItem = $field[0] ?? NULL;
    if (!isset($propertyName)) {
      $propertyName = (is_object($fieldItem) && method_exists($fieldItem, 'mainPropertyName')) ? $fieldItem->mainPropertyName() : 'value';
    }
    $value = (is_object($field) && method_exists($field, 'getValue')) ? $field->getValue() : NULL;
    switch ($condition['operator']) {
      case 'IN':
        if ($value == NULL && !empty($condition['value'])) {
          return FALSE;
        }
        foreach ($value as $valueItem) {
          if (!in_array($valueItem[$propertyName] ?? NULL, $condition['value'])) {
            return FALSE;
          }
        }
        return TRUE;

      case 'NOT IN':
        if ($value == NULL && !empty($condition['value'])) {
          return TRUE;
        }
        foreach ($value as $valueItem) {
          if (in_array($valueItem[$propertyName], $condition['value'])) {
            return FALSE;
          }
        }
        return TRUE;

      // NULL is treated as `=` condition for EntityQery queries.
      case NULL:
      case '=':
        if (is_array($value)) {
          foreach ($value as $valueItem) {
            if (($valueItem[$propertyName] ?? NULL) == $condition['value']) {
              return TRUE;
            }
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
          if (eval("return '" . addslashes($valueItem[$propertyName] ?? NULL) . "' " . $condition['operator'] . " '" . addslashes($condition['value']) . "';")) {
            // @codingStandardsIgnoreEnd
            return TRUE;
          }
        }
        return FALSE;

      default:
        throw new \Exception('A stub for the "' . $condition['operator'] . '" operator is not implemented yet.' . $exceptionSuffix);
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
   * Calls an event subscriber with checking the definition in services.
   *
   * Checks that the event subscriber has a definition in services.yml file
   * and the 'event_subscriber' tag in it, before calling.
   *
   * @param string|array $service
   *   A service class as a string, or an array with the service info, where:
   *   - the first element is a path to the service YAML file,
   *   - the second element - the service name.
   * @param string $eventName
   *   The Event name.
   * @param object $event
   *   The Event object.
   */
  public static function callEventSubscriber($service, string $eventName, object &$event): void {
    if (is_array($service)) {
      [$servicesYamlFile, $serviceName] = $service;
    }
    else {
      // Assuming that the service name is related to a called module.
      // Using there jumping to one level upper when detecting module info,
      // because current call adds a new step already.
      $servicesYamlFile = self::getModuleRoot(1) . '/' . self::getModuleName(1) . '.services.yml';
      $serviceName = $service;
    }
    $serviceInfo = self::getServiceInfoFromYaml($serviceName, $servicesYamlFile);

    // Checking the presence of the 'event_subscriber' tag.
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

    $service = self::initServiceFromYaml($servicesYamlFile, $serviceName);
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
  public static function createPartialMockWithConstructor(string $originalClassName, array $methods, array $constructorArgs = NULL, array $addMethods = NULL): MockObject {
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

  /**
   * Gets a module name from a namespace of a module class.
   *
   * @param string|int|null $namespaceOrLevel
   *   The module class namespace. If NULL - gets the namespace from a called
   *   function. If numeric - jumps upper the passed number of levels.
   *
   * @return string|null
   *   The module name, or NULL if can't find.
   */
  public static function getModuleName($namespaceOrLevel = NULL): ?string {
    $moduleName = NULL;
    if ($namespaceOrLevel === NULL || is_numeric($namespaceOrLevel)) {
      $level = is_numeric($namespaceOrLevel) ? 2 + $namespaceOrLevel : 2;
      $namespace = self::getCallerInfo($level)['class'];
    }
    else {
      $namespace = ltrim($namespaceOrLevel, '\\');
    }
    $parts = explode('\\', $namespace);
    if ($parts[0] === 'Drupal') {
      if ($parts[1] === 'Tests') {
        $moduleName = $parts[2];
      }
      else {
        $moduleName = $parts[1];
      }
    }

    if (
      in_array($moduleName, [
        'Component',
        'Core',
      ])) {
      $moduleName = 'core';
    }

    return $moduleName;
  }

  /**
   * Gets a root module folder from a module file full path.
   *
   * @param string|int|null $pathOrClassOrLevel
   *   A full path to a file or a class. If empty - gets the path of the
   *   function caller file.
   * @param string|null $moduleName
   *   The name of the module, if empty - gets the caller module name.
   *
   * @return string|null
   *   The full path to the module root.
   */
  public static function getModuleRoot($pathOrClassOrLevel = NULL, string $moduleName = NULL): ?string {
    if ($pathOrClassOrLevel === NULL || is_numeric($pathOrClassOrLevel)) {
      // Getting a module info from a caller function.
      $level = is_numeric($pathOrClassOrLevel) ? 2 + $pathOrClassOrLevel : 2;
      $callerInfo = self::getCallerInfo($level);
      $file = $callerInfo['file'];
      $moduleName = self::getModuleName($callerInfo['class']);
    }
    elseif (str_starts_with($pathOrClassOrLevel, 'Drupal\\')) {
      // We have a full path of the Drupal class.
      $file = self::getClassFile($pathOrClassOrLevel);
    }
    else {
      $file = $pathOrClassOrLevel;
    }
    $parts = explode(DIRECTORY_SEPARATOR, $file);
    $partsReversed = array_reverse($parts);
    if ($moduleName == 'core') {
      $modulesIndex = array_search('core', $partsReversed);
    }
    else {
      $modulesIndex =
        array_search('modules', $partsReversed)
        ?? array_search('themes', $partsReversed);
    }
    if (!$modulesIndex) {
      return NULL;
    }
    if ($moduleName) {
      $index = $modulesIndex;
      while ($partsReversed[$index] !== $moduleName && $index > 0) {
        $index--;
      }
      return $index > 0 ? implode(DIRECTORY_SEPARATOR, array_reverse(array_slice($partsReversed, $index))) : NULL;
    }
  }

  /**
   * Checks if the actual version is equal or hiher than requested.
   *
   * @param string $version
   *   A version number to check in format like "10.1", "10", "9.5.3".
   */
  public static function isDrupalVersionAtLeast(string $version): bool {
    $requested = explode('.', $version);
    if (!is_numeric($requested[0])) {
      throw new \Exception("Can't detect major version number from string \"$version\".");
    }
    $actual = explode('.', \DRUPAL::VERSION);
    if (
      $actual[0] < $requested[0]
      || (isset($requested[1]) && $actual[1] < $requested[1])
      || (isset($requested[2]) && $actual[2] < $requested[2])
    ) {
      return FALSE;
    }
    return TRUE;
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
  private static function getServiceStubClass(string $serviceName, array $mockableMethods = NULL, array $addMockableMethods = NULL): ?object {
    $service = NULL;
    if (isset(self::SERVICES_CUSTOM_STUBS[$serviceName])) {
      $serviceClass = self::SERVICES_CUSTOM_STUBS[$serviceName];
      if (is_string($serviceClass)) {
        if (empty($mockableMethods) && empty($addMockableMethods)) {
          $service = new $serviceClass();
        }
        else {
          $service = UnitTestCaseWrapper::getInstance()->createPartialMockWithConstructor($serviceClass, $mockableMethods, [], $addMockableMethods);
        }
      }
      elseif (is_array($serviceClass)) {
        $service = call_user_func_array($serviceClass, []);
      }
      else {
        throw new \Exception("Bad format of parameters for $serviceName.");
      }
    }
    return $service;
  }

  /**
   * Gets a service info from a YAML file.
   */
  private static function getServiceInfoFromYaml(string $serviceName, string $servicesYamlFile): array {
    $services = self::parseYamlFile((str_starts_with($servicesYamlFile, '/') ? '' : self::getDrupalRoot()) . '/' . $servicesYamlFile)['services'];
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
      $services = self::parseYamlFile((str_starts_with($servicesYamlFile, '/') ? '' : self::getDrupalRoot()) . '/' . $servicesYamlFile)['services'];
      $serviceClass = $services[$serviceName]['class'] ?? FALSE;
    }
    else {
      if (isset(self::SERVICES_CUSTOM_STUBS[$serviceName])) {
        $serviceClass = self::SERVICES_CUSTOM_STUBS[$serviceName];
      }
      self::requireCoreFeaturesMap();
      $serviceClass = TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP[$serviceName]['class'] ?? FALSE;
    }
    if (!$serviceClass) {
      throw new \Exception("Service '$serviceName' is missing in the list.");
    }
    return $serviceClass;
  }

  /**
   * Loads a Drupal Core services map file for the correct Drupal Core version.
   *
   * @internal
   *   This function is used mostly for the internal functionality.
   */
  public static function requireCoreFeaturesMap(): void {
    if (defined('TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP')) {
      return;
    }
    $mapDirectory = dirname(__FILE__) . '/includes/CoreFeaturesMaps';
    $filePrefix = 'CoreFeaturesMap';
    [$major, $minor, $patch] = explode('.', \Drupal::VERSION);
    unset($patch);
    while ($major >= 8) {
      $path = "$mapDirectory/$filePrefix.$major.$minor.php";
      if (file_exists($path)) {
        break;
      }
      $minor--;
      if ($minor < 0) {
        $major--;
        $minor = 10;
      }
    }
    require_once $path;
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
    if (is_string($methods)) {
      // When a single method is passed as string.
      $methodsToCall[] = $methods;
    }
    elseif (is_numeric($methods[1] ?? NULL)) {
      // When a single method is passed as array with function and priority.
      $methodsToCall[$methods[1]] = $methods[0];
    }
    else {
      // When a list of methids is passed as array.
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
   * Gets a filename of a caller (parent) function.
   *
   * @param int $level
   *   The level to use when getting a filename. By defualt '2' to get parent of
   *   parent caller, because for parent caller it's easier to use __FILE__
   *   construction.
   *
   * @return array
   *   An array with the caller information:
   *   - file: the full path to file.
   *   - function: the function name.
   *   - class: the full class name.
   *
   * @internal
   *   This function is used mostly for the internal functionality.
   */
  public static function getCallerInfo(int $level = 2): ?array {
    $backtrace = debug_backtrace(defined("DEBUG_BACKTRACE_IGNORE_ARGS") ? DEBUG_BACKTRACE_IGNORE_ARGS : FALSE);
    // The caller filename is located in one level lower.
    $callerTrace = $backtrace[$level - 1] ?? NULL;
    // The caller filename is located in one level lower.
    $calledTrace = $backtrace[$level] ?? NULL;
    if (!$callerTrace || !$calledTrace) {
      return NULL;
    }
    return [
      'file' => $callerTrace['file'] ?? NULL,
      'function' => $calledTrace['function'],
      'class' => $calledTrace['class'],
    ];
  }

  /**
   * Parses a YAML file and caches the result in memory.
   *
   * @param string $servicesFile
   *   A path to a YAML file.
   *
   * @return mixed
   *   A result of file parsing.
   */
  private static function parseYamlFile(string $servicesFile) {
    static $cache;
    if (isset($cache[$servicesFile])) {
      return $cache[$servicesFile];
    }
    $cache[$servicesFile] = Yaml::parseFile($servicesFile);
    return $cache[$servicesFile];
  }

  /**
   * Disables a construtor calls to allow only static calls.
   */
  private function __construct() {
  }

  /**
   * Throws a user error with explanation of a failing match.
   *
   * @param string $subject
   *   The name of the property that was checked.
   * @param mixed $expected
   *   The expected value.
   * @param mixed $actual
   *   The actual value.
   */
  private static function throwMatchError(string $subject, $expected, $actual) {
    trigger_error("The $subject doesn't match, expected: " . var_export($expected, TRUE) . ", actual: " . var_export($actual, TRUE), E_USER_NOTICE);
  }

  /**
   * Throws a user error with a message.
   *
   * @param string $message
   *   A message to throw.
   */
  private static function throwUserError(string $message): void {
    trigger_error($message, E_USER_NOTICE);
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
   * Creates a stub entity for an entity type from a given class.
   *
   * @param string $entityTypeNameOrClass
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
  public static function createEntityStub(string $entityTypeNameOrClass, array $values = [], array $options = []) {
    @trigger_error('Function createEntityStub() is deprecated in test_helpers:1.0.0-beta5 and is removed from test_helpers:1.0.0-rc1. Renamed to createEntity(). See https://www.drupal.org/project/test_helpers/issues/3337449', E_USER_DEPRECATED);
    return self::createEntity($entityTypeNameOrClass, $values, $options);
  }

  /**
   * Creates a stub entity for an entity type from a given class and saves it.
   *
   * @param string $entityTypeNameOrClass
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
  public static function saveEntityStub(string $entityTypeNameOrClass, array $values = [], array $options = []) {
    @trigger_error('Function saveEntityStub() is deprecated in test_helpers:1.0.0-beta5 and is removed from test_helpers:1.0.0-rc1. Renamed to saveEntity(). See https://www.drupal.org/project/test_helpers/issues/3337449', E_USER_DEPRECATED);
    return self::saveEntity($entityTypeNameOrClass, $values, $options);
  }

  /**
   * Gets or initializes an Entity Storage for a given Entity Type class name.
   *
   * @param string $entityTypeNameOrClass
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
  public static function getEntityStorageStub(string $entityTypeNameOrClass, string $annotation = NULL): EntityStorageInterface {
    @trigger_error('Function getEntityStorageStub() is deprecated in test_helpers:1.0.0-beta5 and is removed from test_helpers:1.0.0-rc1. Renamed to getEntityStorage(). See https://www.drupal.org/project/test_helpers/issues/3337449', E_USER_DEPRECATED);
    return self::getEntityStorage($entityTypeNameOrClass, $annotation);
  }

  /**
   * Gets a protected method from a class using reflection.
   *
   * @param object $class
   *   The class instance.
   * @param string $methodName
   *   The name of the method to get.
   *
   * @return \ReflectionMethod
   *   The method instance.
   *
   * @deprecated in test_helpers:1.0.0-beta8 and is removed from
   *   test_helpers:1.0.0-rc1. Renamed to getPrivateMethod().
   * @see https://www.drupal.org/project/test_helpers/issues/3341353
   */
  public static function getProtectedMethod(object $class, string $methodName): \ReflectionMethod {
    return self::getPrivateMethod($class, $methodName);
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
   *
   * @deprecated in test_helpers:1.0.0-beta8 and is removed from
   *   test_helpers:1.0.0-rc1. Renamed to callPrivateMethod().
   * @see https://www.drupal.org/project/test_helpers/issues/3341353
   */
  public static function callProtectedMethod(object $class, string $methodName, array $arguments = []) {
    return self::callPrivateMethod($class, $methodName, $arguments);
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
   *
   * @deprecated in test_helpers:1.0.0-beta8 and is removed from
   *   test_helpers:1.0.0-rc1. Renamed to getPrivateProperty().
   * @see https://www.drupal.org/project/test_helpers/issues/3341353
   */
  public static function getProtectedProperty(object $class, string $propertyName, $returnReflectionProperty = FALSE) {
    return self::getPrivateProperty($class, $propertyName, $returnReflectionProperty);
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
   *
   * @deprecated in test_helpers:1.0.0-beta8 and is removed from
   *   test_helpers:1.0.0-rc1. Renamed to setPrivateProperty().
   * @see https://www.drupal.org/project/test_helpers/issues/3341353
   */
  public static function setProtectedProperty(object $class, string $propertyName, $value): void {
    self::setPrivateProperty($class, $propertyName, $value);
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
   *
   * @deprecated in test_helpers:1.0.0-beta9 and is removed from
   *   test_helpers:1.0.0-rc1. Use initServiceFromYaml().
   * @see https://www.drupal.org/project/test_helpers/issues/3341353
   */
  public static function createServiceFromYaml(string $servicesYamlFile, string $name, array $additionalArguments = NULL, array $services = NULL): object {
    $additionalArguments ??= [];
    if ($services !== NULL) {
      self::setServices($services);
    }
    $serviceInfo = self::getServiceInfoFromYaml($name, $servicesYamlFile);
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
   *
   * @deprecated in test_helpers:1.0.0-beta10 and is removed from
   *   test_helpers:1.0.0-rc1. Use initServiceFromYaml() instead.
   * @see https://www.drupal.org/project/test_helpers/issues/3350342
   */
  public static function createServiceMock(string $serviceName, string $servicesYamlFile = NULL): MockObject {
    $serviceClass = self::getServiceClassByName($serviceName, $servicesYamlFile);
    $service = TestHelpers::createMock($serviceClass);
    self::service($serviceName, $service);
    return $service;
  }

}
