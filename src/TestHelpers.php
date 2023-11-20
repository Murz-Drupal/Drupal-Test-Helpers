<?php

namespace Drupal\test_helpers;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Database\Query\ConditionInterface as DatabaseQueryConditionInterface;
use Drupal\Core\Database\Query\SelectInterface as DatabaseSelectInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\ConditionInterface as EntityQueryConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface as EntityQueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\test_helpers\lib\MockedFunctionCalls;
use Drupal\test_helpers\lib\MockedFunctionStorage;
use Drupal\test_helpers\Stub\CacheContextsManagerStub;
use Drupal\test_helpers\Stub\ConfigFactoryStub;
use Drupal\test_helpers\Stub\ConfigurableLanguageManagerStub;
use Drupal\test_helpers\Stub\DatabaseStorageStub;
use Drupal\test_helpers\Stub\DatabaseStub;
use Drupal\test_helpers\Stub\DateFormatterStub;
use Drupal\test_helpers\Stub\DrupalKernelStub;
use Drupal\test_helpers\Stub\EntityBundleListenerStub;
use Drupal\test_helpers\Stub\EntityFieldManagerStub;
use Drupal\test_helpers\Stub\EntityTypeBundleInfoStub;
use Drupal\test_helpers\Stub\EntityTypeManagerStub;
use Drupal\test_helpers\Stub\LanguageDefaultStub;
use Drupal\test_helpers\Stub\LoggerChannelFactoryStub;
use Drupal\test_helpers\Stub\ModuleHandlerStub;
use Drupal\test_helpers\Stub\PermissionHandlerStub;
use Drupal\test_helpers\Stub\RendererStub;
use Drupal\test_helpers\Stub\RequestStackStub;
use Drupal\test_helpers\Stub\RouteProviderStub;
use Drupal\test_helpers\Stub\TypedDataManagerStub;
use Drupal\test_helpers\Stub\UrlGeneratorStub;
use Drupal\test_helpers\StubFactory\EntityStubFactory;
use Drupal\test_helpers\StubFactory\FieldItemListStubFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
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
    // @todo Get rid of this service.
    'test_helpers.keyvalue.memory' => KeyValueMemoryFactory::class,

    'cache_contexts_manager' => CacheContextsManagerStub::class,
    'cache.config' => MemoryBackend::class,
    'class_resolver' => [self::class, 'getClassResolverStub'],
    'config.factory' => ConfigFactoryStub::class,
    'config.storage.active' => DatabaseStorageStub::class,
    'config.storage.snapshot' => DatabaseStorageStub::class,
    'database' => DatabaseStub::class,
    'date.formatter' => DateFormatterStub::class,
    'entity_bundle.listener' => EntityBundleListenerStub::class,
    'entity_field.manager' => EntityFieldManagerStub::class,
    'entity_type.bundle.info' => EntityTypeBundleInfoStub::class,
    'entity_type.manager' => EntityTypeManagerStub::class,
    'kernel' => DrupalKernelStub::class,
    'language_manager' => ConfigurableLanguageManagerStub::class,
    'language.default' => LanguageDefaultStub::class,
    'logger.factory' => LoggerChannelFactoryStub::class,
    'module_handler' => ModuleHandlerStub::class,
    'renderer' => RendererStub::class,
    'request_stack' => RequestStackStub::class,
    'router.route_provider' => RouteProviderStub::class,
    'string_translation' => [self::class, 'getStringTranslationStub'],
    'typed_data_manager' => TypedDataManagerStub::class,
    'url_generator.non_bubbling' => UrlGeneratorStub::class,
    'user.permissions' => PermissionHandlerStub::class,
  ];

  /**
   * A list of core services that can be initialized automatically.
   */
  public const SERVICES_CORE_INIT = [
    'cache_tags.invalidator',
    'cache.backend.memory',
    'cache.config',
    'config.storage',
    'path.current',
    'database.replica_kill_switch',
    'datetime.time',
    'entity.memory_cache',
    'entity.repository',
    'link_generator',
    'logger.factory',
    'messenger',
    'path_processor_manager',
    'request_stack',
    'router.no_access_checks',
    'session.flash_bag',
    'settings',
    'token',
    'transliteration',
    'unrouted_url_assembler',
    'url_generator',
    'uuid',
  ];

  /**
   * An uri with a placeholder domain, to use in requests by default.
   *
   * It is used to produce absolute links when no request is pushed manually to
   * the 'request_stack' service.
   *
   * You can override this default behavior in your unit test via:
   * ```
   * TestHelpers::service('request_stack')->push(Request::create('https://example.com/some-path');
   * ```
   *
   * Example of how to check the url with the default request stub:
   * ```
   * $this->assertEquals(
   *   TestHelpers::REQUEST_STUB_DEFAULT_URI,
   *   $service->getCurrentRequest()->getUri()
   * );
   * ```
   *
   * @var string
   */
  public const REQUEST_STUB_DEFAULT_URI = 'http://drupal-unit-test.local/';

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
   * @param object $class
   *   The mocked class.
   * @param string $method
   *   The method name.
   * @param \Closure $closure
   *   The closure function to bind.
   */
  public static function setMockedClassMethod(object $class, string $method, \Closure $closure): void {
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
   * @return bool|string
   *   The path to the file.
   */
  public static function getClassFile($class) {
    $reflection = new \ReflectionClass($class);
    return $reflection->getFileName();
  }

  /**
   * Finds a Drupal root directory.
   *
   * @return string
   *   A path to the Drupal root directory.
   */
  public static function getDrupalRoot(): string {
    static $path;
    if (!$path) {
      if (class_exists('\Drupal')) {
        $rc = new \ReflectionClass(\Drupal::class);
        $drupalClassAbsolutePath = $rc->getFileName();
        $drupalClassRelativePath = 'core/lib/Drupal.php';
        $path = substr($drupalClassAbsolutePath, 0, -strlen($drupalClassRelativePath) - 1);
      }
      else {
        $path = __DIR__;
        while (!file_exists($path . '/core/lib/Drupal.php')) {
          $path = dirname($path);
          if ($path == '') {
            throw new \Exception('Drupal root directory cannot be found.');
          }
        }
      }
    }
    return $path;
  }

  /**
   * Asserts that a function throws a specific exception.
   *
   * @param callable $function
   *   A function to execute.
   * @param string $exceptionClass
   *   (optional) An exception class to assert, \Exception by default.
   * @param string $message
   *   (optional) A message text to throw on missing exception.
   *
   * @todo Cover this function by a unit test.
   */
  public static function assertException(callable $function, string $exceptionClass = NULL, string $message = NULL) {
    $exceptionClass ??= '\Exception';
    $message ??= "An exception instance of $exceptionClass is expected.";
    try {
      $function();
      Assert::fail($message);
    }
    catch (\Throwable $e) {
      if (
        !$e instanceof AssertionFailedError
        && $e instanceof $exceptionClass
      ) {
        Assert::assertInstanceOf($exceptionClass, $e);
        return;
      }
      throw $e;
    }
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
      static $annotatedClassDiscovery;
      $annotatedClassDiscovery ??= new AnnotatedClassDiscovery('', new \ArrayObject([]));
      // @todo Rework without calling a private method.
      TestHelpers::callPrivateMethod(
        $annotatedClassDiscovery,
        'prepareAnnotationDefinition',
        [$annotation, $class]
      );

      $definition = $annotation->get();

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
   *   The list of arguments for passing to the function create(), excluding
   *   the container as the first argument, because it is mandatory, so it is
   *   passed automatically.
   * @param array $services
   *   The array of services to add to the container.
   *   Format is same as in function setServices().
   *
   * @return object
   *   The initialized class instance.
   */
  public static function createClass($class, array $createArguments = NULL, array $services = NULL): object {
    if ($services) {
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
   * @param string|null $overrideClass
   *   A class to override the default service class.
   *
   * @return object
   *   The initialized class instance.
   */
  public static function initServiceFromYaml(
    $servicesYamlFileOrData,
    string $name,
    array $mockMethods = NULL,
    string $overrideClass = NULL
  ): object {
    if (is_string($servicesYamlFileOrData)) {
      $serviceInfo = self::getServiceInfoFromYaml($name, $servicesYamlFileOrData);
      $serviceInfo['class'] ??= $name;
    }
    elseif (is_array($servicesYamlFileOrData)) {
      $serviceInfo = $servicesYamlFileOrData;
    }
    else {
      throw new \Error('The first argument should be a path to a YAML file, or array with data.');
    }
    return self::initServiceFromInfo($serviceInfo, $mockMethods);
  }

  /**
   * Replaces parameters and services to real values in service arguments.
   *
   * @param array $arguments
   *   A list of raw arguments.
   *
   * @return array
   *   A list of resolved arguments.
   */
  private static function resolveServiceArguments(array $arguments = []): array {
    $container = self::getContainer();
    if (!$container->hasParameter('app.root')) {
      self::loadParametersFromYamlFile(self::getDrupalRoot() . DIRECTORY_SEPARATOR . 'core/core.services.yml');
    }
    $classArguments = [];
    foreach ($arguments as $argument) {
      $firstCharacter = substr($argument ?? '', 0, 1);
      if ($firstCharacter == '@') {
        $classArguments[] = self::service(substr($argument, 1));
      }
      elseif ($firstCharacter == '%') {
        $key = trim($argument, '%');
        if ($container->hasParameter($key)) {
          $resolved = $container->getParameter($key);
        }
        else {
          switch ($key) {
            case 'language.default_values':
              $resolved = Language::$defaultValues;
              $resolved['label'] = $resolved['name'];
              break;

            case 'cache_contexts':
              $resolved = [];
              break;

            case 'container.modules':
              $resolved = [];
              break;

            default:
              throw new \Error("Container parameter '$key' is missing.\nAdd it using TestHelpers::getContainer()->setParameter('$key', \$value');");
          }
        }
        $classArguments[] = $resolved;
      }
      else {
        $classArguments[] = $argument;
      }
    }
    return $classArguments;
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
   * @param string|null $overrideClass
   *   A class to override the default service class.
   *
   * @return object
   *   The initialized class instance.
   */
  public static function initService(
    string $serviceNameOrClass,
    string $serviceNameToCheck = NULL,
    array $mockMethods = NULL,
    string $overrideClass = NULL
  ): object {
    // If we have just a service name, not a class.
    if (strpos($serviceNameOrClass, '\\') === FALSE) {
      $serviceName = $serviceNameOrClass;
      self::requireCoreFeaturesMap();
      if (isset(TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP[$serviceName])) {
        return self::initServiceFromYaml(TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP[$serviceName], $serviceName, $mockMethods, $overrideClass);
      }
      else {
        // We have a service id name, use the current module as the module name.
        $callerInfo = self::getCallerInfo();
        $moduleName = self::getModuleName($callerInfo['class']);
        $moduleRoot = self::getModuleRoot($callerInfo['file'], $moduleName);
        $servicesFile = "$moduleRoot/$moduleName.services.yml";
        return self::initServiceFromYaml($servicesFile, $serviceName, $mockMethods, $overrideClass);
      }
    }
    else {
      $serviceClass = ltrim($serviceNameOrClass, '\\');
      $serviceInfo = self::getServiceInfoFromClass($serviceClass);
      if (!$serviceInfo) {
        throw new \Exception("Can't find the service name by class $serviceClass.");
      }
      $serviceName = $serviceInfo['#name'];
      $servicesFile = $serviceInfo['#file'];
      if ($serviceNameToCheck && $serviceNameToCheck !== $serviceName) {
        throw new \Exception("The service name '$serviceName' differs from required name '$serviceNameToCheck'");
      }
      return self::initServiceFromYaml($servicesFile, $serviceName, $mockMethods, $overrideClass);
    }
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
    foreach ($servicesFileData['services'] ?? [] as $name => $info) {
      if (isset($info['class'])) {
        $checkingClass = ltrim($info['class'], '\\');
      }
      else {
        $checkingClass = ltrim($name, '\\');
      }
      if ($checkingClass == $serviceClass) {
        $info['class'] = $checkingClass;
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
      $container->set('kernel', new DrupalKernelStub());
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
   * @param array $mockMethods
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
    array $mockMethods = NULL,
    array $addMockableMethods = NULL,
    bool $initService = NULL
  ): object {
    $addMockableMethods ??= [];
    $container = self::getContainer();
    if ($container->has($serviceName) && $class === NULL && !$forceOverride) {
      return $container->get($serviceName);
    }

    // Only use stubs if the class is not explicitly set.
    if ($class == NULL) {
      $serviceStub = self::SERVICES_CUSTOM_STUBS[$serviceName] ?? NULL;
    }
    else {
      $serviceStub = NULL;
    }

    if ($initService === NULL) {
      if (
        in_array($serviceName, self::SERVICES_CORE_INIT)
        || $serviceStub
      ) {
        $initService = TRUE;
      }
      else {
        $initService = FALSE;
      }
    }

    if (is_object($class)) {
      $service = $class;
    }
    elseif (is_string($class)) {
      if ($initService) {
        $service = self::initService($class, NULL, $mockMethods);
      }
      else {
        // @todo Add $addMockableMethods.
        $service = self::createMock($class);
      }
    }
    elseif ($class === NULL) {
      $serviceInfo = self::getServiceInfo($serviceName);

      if ($initService) {
        if (is_array($serviceStub)) {
          $service = call_user_func_array($serviceStub, []);
        }
        else {
          if (isset($serviceStub)) {
            $serviceInfo['class'] = $serviceStub;
          }
          $service = self::initServiceFromInfo($serviceInfo, $mockMethods);
        }
      }
      else {
        $service = self::createMock($serviceInfo['class']);
      }
    }
    else {
      throw new \Exception("Class should be an object, string as path to class, or NULL.");
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
   * Initializes a service from the services info array from YAML file.
   *
   * @param array $info
   *   An array with service information, like in services YAML file.
   * @param array $mockMethods
   *   A list of methods to mock.
   *
   * @return object
   *   The service instance.
   */
  private static function initServiceFromInfo(array $info, array $mockMethods = NULL) {
    $info['arguments'] ??= [];
    if (isset($info['arguments'])) {
      $info['arguments'] = self::resolveServiceArguments($info['arguments']);
    }
    if ($mockMethods) {
      $service = TestHelpers::createPartialMockWithConstructor(
        $info['class'],
        $mockMethods,
        $info['arguments'],
      );
    }
    else {
      if (isset($info['factory'])) {
        // @todo Add call a factory instead of initializing a stub.
        // $service = call_user_func($info['factory'], $info['arguments']);
        $service = new $info['class'](...$info['arguments']);
      }
      else {
        $service = new $info['class'](...$info['arguments']);
      }
    }
    // @todo Implement all calls.
    // if ($instance instanceof ContainerAwareInterface) {
    if (method_exists($service, 'setContainer')) {
      $service->setContainer(self::getContainer());
    }
    return $service;
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
   *     Supportable formats:
   *     - A string, indicating field type, like 'integer', 'string',
   *       'entity_reference', only core field types are supported.
   *     - An array with field configuration: type, settings, etc, like this:
   *       [
   *        'type' => 'entity_reference',
   *        'settings' => ['target_type' => 'node']
   *        'translatable' => TRUE,
   *        'required' => FALSE,
   *        'cardinality' => 3,
   *       ].
   *     - A field definition object, that will be applied to the field.
   *
   * @return \Drupal\test_helpers\Stub\EntityStubInterface|\Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject
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
   *     Supportable formats:
   *     - A string, indicating field type, like 'integer', 'string',
   *       'entity_reference', only core field types are supported.
   *     - An array with field configuration: type, settings, etc, like this:
   *       [
   *        'type' => 'entity_reference',
   *        'settings' => ['target_type' => 'node']
   *        'translatable' => TRUE,
   *        'required' => FALSE,
   *        'cardinality' => 3,
   *       ].
   *     - A field definition object, that will be applied to the field.
   *
   * @return \Drupal\test_helpers\Stub\EntityStubInterface|\Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject
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
   * @param bool|null $forceOverride
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
  public static function getEntityStorage(string $entityTypeNameOrClass, EntityStorageInterface $storageInstance = NULL, ?bool $forceOverride = NULL, array $storageOptions = NULL): EntityStorageInterface {
    return self::service('entity_type.manager')->stubGetOrCreateStorage($entityTypeNameOrClass, $storageInstance, $forceOverride, $storageOptions);
  }

  /**
   * Creates a field instance stub.
   *
   * @param array|string|null $values
   *   The field values.
   * @param string|\Drupal\Core\Field\FieldDefinitionInterface|null $typeOrDefinition
   *   A field type like 'string', 'integer', 'boolean'.
   *   Or a path to a field class like
   *   Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem.
   *   Or a ready definition object to use.
   *   If null - will be created a stub with fallback ItemStubItem definition.
   * @param string|null $name
   *   The field name.
   * @param \Drupal\Core\TypedData\TypedDataInterface|null $parent
   *   Parent item for attaching to the field.
   * @param bool|null $isBaseField
   *   A flag to create a base field instance.
   * @param array|null $mockMethods
   *   A list of method to mock when creating the instance.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   A field item list with items as stubs.
   */
  public static function createFieldStub(
    $values = NULL,
    $typeOrDefinition = NULL,
    string $name = NULL,
    TypedDataInterface $parent = NULL,
    $isBaseField = NULL,
    array $mockMethods = NULL
  ): FieldItemListInterface {
    return FieldItemListStubFactory::create($name, $values, $typeOrDefinition, $parent, $isBaseField, $mockMethods);
  }

  /**
   * Adds a field plugin from class to the typed data manager.
   *
   * @param string $class
   *   The field plugin class.
   */
  public static function addFieldPlugin(string $class): void {
    TestHelpers::service('typed_data_manager')->stubAddFieldType($class);
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
    self::service('entity_type.manager');
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
   * @param bool|null $onlyListed
   *   Forces to return false, if the checking query object contains more
   *   conditions than in object with expected conditions.
   * @param bool|null $throwErrors
   *   Enables throwing notice errors when matching fails, with the explanation
   *   what exactly doesn't match.
   *
   * @return bool
   *   True if is subset, false if not.
   */
  public static function matchConditions(object $conditionsObject, object $conditionsExpectedObject, bool $onlyListed = NULL, bool $throwErrors = FALSE): bool {
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
            break;
          }
        }
        elseif ($condition == $conditionExpected) {
          if (is_array($condition['value'])) {
            if ($condition['value'] == $conditionExpected['value']) {
              $conditionsExpectedFound[$conditionsExpectedDelta] = TRUE;
              $conditionsFound[$conditionDelta] = TRUE;
              break;
            }
          }
          else {
            $conditionsExpectedFound[$conditionsExpectedDelta] = TRUE;
            $conditionsFound[$conditionDelta] = TRUE;
            break;
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
            $throwErrors && self::throwUserError('The expected condition group "' . $condition['field']->getConjunction() . '" is not matching, items: ' . self::shorthandVarExport($groupConditions, TRUE));
            return FALSE;
          }
          $throwErrors && self::throwUserError('The expected condition is not found: ' . self::shorthandVarExport($condition, TRUE));
          return FALSE;
        }
      }
      $throwErrors && self::throwMatchError('count of matched conditions', count($conditionsExpected), count($conditionsExpectedFound));
      return FALSE;
    }
    if ($onlyListed && (count($conditions) != count($conditionsExpected))) {
      foreach ($conditions as $delta => $condition) {
        if (!isset($conditionsFound[$delta])) {
          $throwErrors && self::throwUserError('The condition is not listed in expected: ' . self::shorthandVarExport($condition, TRUE));
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
    switch (is_string($condition['operator']) ? strtoupper($condition['operator']) : $condition['operator']) {
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
   * @param bool $throwErrors
   *   Enables throwing notice errors when matching fails, with the explanation
   *   what exactly doesn't match.
   *
   * @return bool
   *   True if the array is the subset, false if not.
   */
  public static function isNestedArraySubsetOf($array, $subset, bool $throwErrors = FALSE): bool {
    static $throwErrorsStatic;

    $callbackFunction = self::class . '::isValueSubsetOfCallback';
    $callerInfo = self::getCallerInfo();
    if ($callerInfo['class'] . '::' . $callerInfo['function'] !== $callbackFunction) {
      $throwErrorsStatic = $throwErrors;
    }
    if ($subset === NULL) {
      return TRUE;
    }
    if (!is_array($array)) {
      $throwErrorsStatic && self::throwMatchError('array', $subset, $array);
      return FALSE;
    }
    if (!is_array($subset)) {
      $throwErrorsStatic && self::throwMatchError('subset', $subset, $array);
      return FALSE;
    }
    $result = array_uintersect_assoc($subset, $array, $callbackFunction);
    if ($result != $subset) {
      $throwErrorsStatic && self::throwMatchError('arrays', $subset, $array);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Calls an event subscriber with checking the definition in services.
   *
   * Checks that the event subscriber has a definition in services.yml file
   * and the 'event_subscriber' tag in it, before calling.
   *
   * @param string|array|object $service
   *   A service class as a string, or an array with the service info, where:
   *   - the first element is a path to the service YAML file,
   *   - the second element - the service name.
   * @param string $eventName
   *   The Event name.
   * @param object $event
   *   The Event object.
   */
  public static function callEventSubscriber($service, string $eventName, object &$event): void {
    if (!is_object($service)) {
      if (is_array($service)) {
        [$servicesYamlFile, $serviceName] = $service;
      }
      elseif (is_string($service)) {
        // Assuming that the service name is related to a called module.
        // Using there jumping to one level upper when detecting module info,
        // because current call adds a new step already.
        $servicesYamlFile = self::getModuleRoot(1) . DIRECTORY_SEPARATOR . self::getModuleName(1) . '.services.yml';
        $serviceName = $service;
      }
      else {
        throw new \Exception('The service parameter is in wrong format.');
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
    }
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
    elseif (
      str_starts_with($pathOrClassOrLevel, 'Drupal\\')
      || str_starts_with($pathOrClassOrLevel, '\\Drupal\\')
    ) {
      // We have a full path of the Drupal class.
      $file = self::getClassFile($pathOrClassOrLevel);
      if (!$moduleName) {
        $moduleName = self::getModuleName($pathOrClassOrLevel);
      }
    }
    else {
      $file = $pathOrClassOrLevel;
    }
    $parts = explode(DIRECTORY_SEPARATOR, $file);

    // Trying to scan all upper directories and find module info file.
    $moduleInfoFile = $moduleName . '.info.yml';
    $coreRootInfoFile = 'core.services.yml';
    $index = count($parts);
    while ($index > 0) {
      $directory = implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, $index));
      if (
        file_exists($directory . DIRECTORY_SEPARATOR . $moduleInfoFile)
        || file_exists($directory . DIRECTORY_SEPARATOR . $coreRootInfoFile)
      ) {
        return $directory;
      }
      $index--;
    }
    return NULL;
  }

  /**
   * Gets the absolute path to a file in the called module by a relative path.
   *
   * Usually used for woring with module's YAML files, like
   * `config/install/my_module.settings.yml` or `my_module.links.menu.yml`.
   *
   * The module root is detected by the location of the file, from which this
   * function is called. Use $parentCallsLevel more than zero, if you call this
   * function from an intermediate class.
   *
   * @param string $relativePath
   *   A realative path to a file, from the module root directory.
   * @param int|null $parentCallsLevel
   *   An optional level to skip some parent calls, if you need to detect the
   *   module from a parent function, not from which you call this function.
   *
   * @return string
   *   A full path to the module file.
   */
  public static function getModuleFilePath(string $relativePath, int $parentCallsLevel = NULL) {
    // We should increase a level by one, to bypass this function call.
    $parentCallsLevel ??= 0;
    $parentCallsLevel++;
    $modulePath = TestHelpers::getModuleRoot($parentCallsLevel);
    return $modulePath . DIRECTORY_SEPARATOR . $relativePath;
  }

  /**
   * Gets the static storage for a mocked PHP function.
   *
   * @param string $functionPath
   *   A full path to a function, like \Drupal\my_module\MyFeature\fopen.
   *   Or a special value '__ALL__' to get all defined storages.
   *
   * @internal
   *   This function is a helper function for the mockPhpFunction().
   *
   * @return \Drupal\test_helpers\lib\MockedFunctionStorage|array
   *   A class with the function storage, or an array with storages if the
   *   $functionPath == '__ALL__'.
   */
  public static function mockPhpFunctionStorage(string $functionPath) {
    static $storages;
    if ($functionPath == '__ALL__') {
      return $storages;
    }
    if (!isset($storages[$functionPath])) {
      $storage = new MockedFunctionStorage();
      $storages[$functionPath] = $storage;
    }
    $storageReference = $storages[$functionPath];
    return $storageReference;
  }

  /**
   * Sets a mock for a PHP build-in function for the namespace of a class.
   *
   * Warning! The function will be mocked for all classes in the passed class
   * namespace, and will stay for all other test function in the file. So,
   * always use TestHelpers::unmockPhpFunction() at the end of each test.
   *
   * For your tests you call it in tearDownAfterClass() to always revert all
   * mocks after finishing the current unit test, to not affect next tests.
   *
   * @param string $name
   *   The function name.
   * @param string $class
   *   The full class name (FQCN) to get the namespace.
   * @param callable|null $callback
   *   A callback function to call, or NULL if no callback is needed.
   *
   * @return \Drupal\test_helpers\lib\MockedFunctionCalls
   *   A MockedFunctionCalls object, containing list of all function calls.
   */
  public static function mockPhpFunction(string $name, string $class, callable $callback = NULL): MockedFunctionCalls {
    $namespace = implode("\\", array_slice(explode("\\", ltrim($class, '\\')), 0, -1));
    $functionPath = $namespace . '\\' . $name;
    $storage = self::mockPhpFunctionStorage($functionPath);
    $storage->isUnmocked = FALSE;
    $storage->callback = $callback;
    $storage->calls = new MockedFunctionCalls();

    // If the mocked function is not defined yet, evaulating the dynamic
    // definition of it.
    if (!function_exists($functionPath)) {
      $code = <<<EOT
namespace $namespace;

use Drupal\\test_helpers\TestHelpers;

function $name() {
  \$storage = TestHelpers::mockPhpFunctionStorage('$functionPath');
  \$args = func_get_args();
  if (\$storage->isUnmocked == TRUE) {
    return \\$name(...\$args);
  }
  \$storage->calls[] = \$args;
  if (isset(\$storage->callback)) {
    \$callback = \$storage->callback;
    return \$callback(...\$args);
  }
}
EOT;
      // To suppress `The use of function eval() is discouraged` warning.
      // @codingStandardsIgnoreStart
      eval($code);
      // @codingStandardsIgnoreEnd
    }
    return $storage->calls;
  }

  /**
   * Unmocks the previously mocked PHP build-in function in a namespace.
   *
   * @param string $name
   *   The function name.
   * @param string $class
   *   The full class name (FQCN) to get the namespace.
   */
  public static function unmockPhpFunction(string $name, string $class) {
    $namespace = implode("\\", array_slice(explode("\\", ltrim($class, '\\')), 0, -1));
    $functionPath = $namespace . '\\' . $name;
    $storage = self::mockPhpFunctionStorage($functionPath);
    $storage->isUnmocked = TRUE;
    $storage->calls = new MockedFunctionCalls();
  }

  /**
   * Unmocks all functions, that was mocked by mockPhpFunction().
   */
  public static function unmockAllPhpFunctions() {
    $storage = self::mockPhpFunctionStorage('__ALL__');
    foreach ($storage as $item) {
      $item->isUnmocked = TRUE;
      $item->calls = new MockedFunctionCalls();
    }
  }

  /* ************************************************************************ *
   * Internal functions.
   * ************************************************************************ */

  /**
   * An internal callback helper function for array_uintersect.
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
   * Replaces all objects to string representation in a nested array.
   *
   * @param array $array
   *   The array to use.
   *
   * @return array
   *   A copy of the array with replaced objects to strings.
   */
  private static function arrayObjectsToStrings(array $array): array {
    $arrayCopy = $array;
    // $arrayCopy = json_decode(json_encode($array), TRUE);
    array_walk_recursive($arrayCopy, self::class . '::arrayObjectsToStringsCallback');
    return $arrayCopy;
  }

  /**
   * Makes an in-place replacement of an object to string in an array item.
   *
   * An internal callback helper function for arrayObjectsToStrings.
   *
   * @param mixed $item
   *   An array item value.
   */
  private static function arrayObjectsToStringsCallback(&$item) {
    if (is_object($item)) {
      $item = '[object] ' . get_class($item) . ', id ' . spl_object_id($item);
    }
  }

  /**
   * An improved version of var_export that outputs arrays in short format.
   *
   * @param mixed $value
   *   A value to use.
   * @param mixed $return
   *   If used and set to true, will return the variable representation instead
   *   of outputting it.
   *
   * @return string|null
   *   A string representation of the value, if $return is true.
   */
  private static function shorthandVarExport($value, $return = FALSE) {
    $export = var_export($value, TRUE);
    $patterns = [
      "/array \(/" => '[',
      "/^([ ]*)\)(,?)$/m" => '$1]$2',
      "/\s\=\>\s+\n\s+\[/" => ' => [',
      "/\[\n\s+\]/" => '[]',
    ];
    $output = preg_replace(array_keys($patterns), array_values($patterns), $export);
    if ($return) {
      return $output;
    }
    else {
      echo $output;
    }
  }

  /**
   * Load params from YAML file to the current service container.
   *
   * @param string $file
   *   A path to a YAML file.
   * @param bool $override
   *   A flag to override the current parameters.
   */
  public static function loadParametersFromYamlFile(string $file, bool $override = TRUE): void {
    $container = self::getContainer();
    $content = self::parseYamlFile($file);
    foreach ($content['parameters'] ?? [] as $key => $value) {
      if ($override || !$container->hasParameter($key)) {
        $container->setParameter($key, $value);
      }
    }
  }

  /**
   * Gets a service info from a YAML file.
   */
  private static function getServiceInfoFromYaml(string $serviceName, string $servicesYamlFile, bool $skipLoadingParams = FALSE): array {
    $filePath = (str_starts_with($servicesYamlFile, DIRECTORY_SEPARATOR) ? '' : self::getDrupalRoot()) . DIRECTORY_SEPARATOR . $servicesYamlFile;
    $info = self::getServiceInfo($serviceName, $filePath);
    if (!$skipLoadingParams) {
      self::loadParametersFromYamlFile($filePath);
    }
    return $info;
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
  private static function getServiceInfo(string $serviceName, string $servicesYamlFile = NULL): array {
    if ($serviceName == 'kernel') {
      $info = [
        'class' => DrupalKernel::class,
      ];
      return $info;
    }
    if ($servicesYamlFile === NULL) {
      self::requireCoreFeaturesMap();
      if (isset(TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP[$serviceName])) {
        $file = self::getDrupalRoot() . DIRECTORY_SEPARATOR . TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP[$serviceName];
      }
      // If we have a path to a Drupal class.
      else {
        // Trying to auto detect the location of the services file.
        $calledModulePath = self::getModuleRoot();
        $calledModuleName = self::getModuleName();
        $file = "$calledModulePath/$calledModuleName.services.yml";
      }
    }
    else {
      $file = (str_starts_with($servicesYamlFile, DIRECTORY_SEPARATOR) ? '' : self::getDrupalRoot()) . DIRECTORY_SEPARATOR . $servicesYamlFile;
    }
    $serviceYaml = self::parseYamlFile($file);
    if (isset($serviceYaml['services'][$serviceName])) {
      $info = $serviceYaml['services'][$serviceName];
    }
    elseif (isset(self::SERVICES_CUSTOM_STUBS[$serviceName])) {
      $info = [
        'class' => self::SERVICES_CUSTOM_STUBS[$serviceName],
      ];
    }
    else {
      throw new \Exception("Service '$serviceName' is not found in the list of core services and in the current module file ($file).");
    }
    if (isset($info['parent'])) {
      $infoParent = self::getServiceInfo($info['parent']);
      if (!isset($info['class'])) {
        $info['class'] = $infoParent['class'];
      }
      if (isset($infoParent['arguments'])) {
        $info['arguments'] =
          [...$infoParent['arguments'], ...($info['arguments'] ?? [])];
      }
      if (isset($infoParent['factory'])) {
        $info['factory'] = $infoParent['factory'];
      }
    }
    return $info;
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
    $mapDirectory = dirname(__FILE__) . '/lib/CoreFeaturesMaps';
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
    $cache ??= [];
    if (isset($cache[$servicesFile])) {
      return $cache[$servicesFile];
    }
    if (file_exists($servicesFile)) {
      $cache[$servicesFile] = Yaml::parseFile($servicesFile);
    }
    return $cache[$servicesFile] ?? NULL;
  }

  /**
   * Disables a construtor calls to allow only static calls.
   *
   * @codeCoverageIgnore
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
    trigger_error(
      "The $subject doesn't match, expected: "
      . self::shorthandVarExport(is_array($expected) ? self::arrayObjectsToStrings($expected) : $expected, TRUE)
      . "\nactual: " . self::shorthandVarExport(is_array($actual) ? self::arrayObjectsToStrings($actual) : $actual, TRUE), E_USER_NOTICE);
  }

  /**
   * Throws a user error with a message.
   *
   * @param string $message
   *   A message to throw.
   *
   * @internal For internal usage to throw user errors.
   */
  public static function throwUserError(string $message): void {
    trigger_error($message, E_USER_NOTICE);
  }

}
