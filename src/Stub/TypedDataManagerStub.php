<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * A stub of the Drupal's default TypedDataManager class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 *
 * @phpstan-ignore-next-line We still need to alter the plugin declaration.
 */
class TypedDataManagerStub extends TypedDataManager {

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    if (!isset($this->definitions[$plugin_id])) {
      $this->tryLoadDefinition($plugin_id);
    }
    return parent::getDefinition($plugin_id, $exception_on_invalid);
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
        $suffixes = ['Item'];
        // @todo Add ability to add namespaces manually from a test.
        $namespaces = [
          'Drupal\Core\Field\Plugin\Field\FieldType',
          'Drupal\text\Plugin\Field\FieldType',
          'Drupal\image\Plugin\Field\FieldType',
          'Drupal\link\Plugin\Field\FieldType',
          'Drupal\test_helpers\Plugin\Field\FieldType',
        ];
        break;

      case NULL:
        switch ($plugin_id) {
          // Hardcoding some known plugin classes.
          case 'entity':
            $this->stubSetPlugin(EntityAdapter::class);
            return TRUE;

          case 'list':
            $this->stubSetPlugin(ItemList::class);
            return TRUE;

          default:
            /*
             * For null category there is probably a DataType plugin, trying
             * match them by name.
             * Match examples:
             * string => Drupal\Core\TypedData\Plugin\DataType\StringData
             * integer => Drupal\Core\TypedData\Plugin\DataType\IntegerData
             * language => Drupal\Core\TypedData\Plugin\DataType\Language
             * entity_reference =>
             *   Drupal\Core\Entity\Plugin\DataType\EntityReference
             */
            $name = $plugin_id;
            $plugin = 'TypedData';
            $suffixes = ['', 'Data'];
            // @todo Add ability to add namespaces manually from a test.
            $namespaces = [
              'Drupal\Core\TypedData\Plugin\DataType',
              'Drupal\Core\Entity\Plugin\DataType',
              'Drupal\test_helpers\DataType',
            ];
            break;
        }
        break;

      default:
        // @todo Add check for other plugin ids.
        return FALSE;

    }

    $className = (new CamelCaseToSnakeCaseNameConverter(NULL, FALSE))->denormalize($name);
    foreach ($namespaces as $namespace) {
      foreach ($suffixes as $suffix) {
        if (class_exists($classNameFull = $namespace . '\\' . $className . $suffix)) {
          $this->stubSetPlugin($classNameFull, $plugin, $category);
          return TRUE;
        }
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
    $this->definitions[$id] = new $class($definitionObject);
    $this->definitions[$id]->setTypedDataManager($this);
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
    $this->definitions[self::getIdWithNamespace($definition['id'], $namespace)] = $definition;
  }

  /**
   * Registers a new field type by plugin class.
   *
   * @param string $class
   *   The class name.
   */
  public function stubAddFieldType(string $class): void {
    $this->stubSetPlugin($class, 'Field', 'field_item');
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
    $this->definitions[self::getIdWithNamespace($customId ?? $definition['id'], $namespace)] = $definition;
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
