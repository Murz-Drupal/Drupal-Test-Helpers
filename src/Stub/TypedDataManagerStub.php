<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\test_helpers\TestHelpers;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * A stub of the Drupal's default TypedDataManager class.
 */
class TypedDataManagerStub extends TypedDataManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces = NULL,
    CacheBackendInterface $cache_backend = NULL,
    ModuleHandlerInterface $module_handler = NULL,
    ClassResolverInterface $class_resolver = NULL
  ) {
    $namespaces ??= new \ArrayObject([]);
    $cache_backend ??= TestHelpers::service('cache.backend.memory')->get('cache_discovery');
    $module_handler ??= TestHelpers::service('module_handler');
    $class_resolver ??= TestHelpers::service('class_resolver');
    parent::__construct($namespaces, $cache_backend, $module_handler, $class_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    if (!isset($this->stubPluginsDefinition[$plugin_id])) {
      $this->tryLoadDefinition($plugin_id);
    }
    return $this->stubPluginsDefinition[$plugin_id] ?? NULL;
  }

  /**
   * Tries to find a suitable definition class by plugin_id and load it.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return bool
   *   Is the plugin found and added.
   */
  protected function tryLoadDefinition(string $plugin_id): bool {
    if (strpos($plugin_id, ':')) {
      [$category, $name] = explode(':', $plugin_id);
    }
    else {
      $category = NULL;
    }
    switch ($category) {
      // @todo Add other plugin categories here.
      case 'field_item':
        $plugin = 'Field';
        $className = (new CamelCaseToSnakeCaseNameConverter(NULL, FALSE))->denormalize($name) . 'Item';
        $namespace = 'Drupal\Core\Field\Plugin\Field\FieldType';
        if (class_exists($classNameFull = $namespace . '\\' . $className)) {
          $this->stubSetPlugin($classNameFull, $plugin, $category);
          return TRUE;
        }
        else {
          return FALSE;
        }

      case NULL:
        // Load pre-defined plugins for some known plugin_id.
        switch ($plugin_id) {
          case 'entity':
            $this->stubSetPlugin(EntityAdapter::class);
            return TRUE;

          case 'list':
            $this->stubSetPlugin(ItemList::class);
            return TRUE;

          case 'string':
            $this->stubSetPlugin(StringData::class);
            return TRUE;
        }
    }
    return FALSE;
  }

  /**
   * Initiates a plugin in stub.
   *
   * @param string $class
   *   The class.
   * @param string $plugin
   *   The plugin name.
   * @param string|null $namespace
   *   The namespace to use.
   */
  public function stubInitPlugin(string $class, string $plugin = 'TypedData', string $namespace = NULL): void {
    $definition = TestHelpers::getPluginDefinition($class, $plugin);
    $id = self::getIdWithNamespace($definition['id'], $namespace);

    if (!isset($definition['list_class'])) {
      $definition['list_class'] = 'Drupal\Core\Field\FieldItemList';
    }

    $definitionClass = $definition['definition_class'];
    $definitionObject = new $definitionClass($definition);
    $this->stubPluginsDefinition[$id] = new $class($definitionObject);
    $this->stubPluginsDefinition[$id]->setTypedDataManager($this);
  }

  /**
   * Sets a plugin to stub.
   *
   * @param string $class
   *   The class name.
   * @param string $plugin
   *   The plugin name.
   * @param string|null $namespace
   *   The namespace to use.
   */
  public function stubSetPlugin(string $class, string $plugin = 'TypedData', string $namespace = NULL): void {
    $definition = TestHelpers::getPluginDefinition($class, $plugin);
    if (!isset($definition['list_class'])) {
      $definition['list_class'] = 'Drupal\Core\Field\FieldItemList';
    }
    $this->stubPluginsDefinition[self::getIdWithNamespace($definition['id'], $namespace)] = $definition;
  }

  /**
   * Sets a definition to stub.
   *
   * @param mixed $definition
   *   The definition.
   * @param string|null $namespace
   *   The namespace to use.
   * @param string|null $customId
   *   Sets the custom id, if needed.
   */
  public function stubSetDefinition($definition, string $namespace = NULL, string $customId = NULL): void {
    $this->stubPluginsDefinition[self::getIdWithNamespace($customId ?? $definition['id'], $namespace)] = $definition;
  }

  /**
   * Sets a definition from class.
   *
   * @param string $class
   *   The class name.
   * @param string $plugin
   *   The plugin name.
   * @param string|null $namespace
   *   The namespace to use.
   */
  public function stubSetDefinitionFromClass(string $class, string $plugin = 'TypedData', string $namespace = NULL): void {
    $definition = TestHelpers::getPluginDefinition($class, $plugin);
    self::stubSetDefinition($definition, $plugin, $namespace);
  }

  /**
   * Combined the id with namespace.
   *
   * @param string $id
   *   The id string.
   * @param string|null $namespace
   *   The namespace.
   *
   * @return string
   *   The combined string.
   */
  protected function getIdWithNamespace(string $id, string $namespace = NULL) {
    return $namespace
      ? $namespace . ':' . $id
      : $id;
  }

}
