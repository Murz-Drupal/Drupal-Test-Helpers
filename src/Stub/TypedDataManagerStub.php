<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * A stub of the Drupal's default TypedDataManager class.
 */
class TypedDataManagerStub extends TypedDataManager {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct() {
    $this->stubSetPlugin(EntityAdapter::class);
    $this->stubSetPlugin(ItemList::class);
    $this->stubSetPlugin(StringData::class);
    /* @todo Try to register popular definitions via something like:
     * $instance->stubSetPlugin(StringData::class);
     * $instance->stubSetPlugin(EntityAdapter::class);
     * $instance->stubSetPlugin(EntityReferenceItem::class);
     * $instance->stubSetPlugin(StringData::class, 'TypedData', 'field_item');
     * $instance->stubSetPlugin(StringLongItem::class, 'Field', 'field_item');
     * $definition = UnitTestHelpers::getPluginDefinition(StringLongItem::class, 'Field');
     * $instance->stubSetDefinition($definition, 'field_item');
     * $definition = UnitTestHelpers::getPluginDefinition(BooleanItem::class, 'Field');
     * $instance->stubSetDefinition($definition, 'field_item');
     */
  }

  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->stubPluginsDefinition[$plugin_id] ?? NULL;
  }

  public function stubInitPlugin($class, $plugin = 'TypedData', $namespace = NULL) {
    $definition = UnitTestHelpers::getPluginDefinition($class, $plugin);
    $id = self::getIdWithNamespace($definition['id'], $namespace);

    if (!isset($definition['list_class'])) {
      $definition['list_class'] = 'Drupal\Core\Field\FieldItemList';
    }

    $definitionClass = $definition['definition_class'];
    $definitionObject = new $definitionClass($definition);
    $this->stubPluginsDefinition[$id] = new $class($definitionObject);
    $this->stubPluginsDefinition[$id]->setTypedDataManager($this);
  }

  public function stubSetPlugin($class, $plugin = 'TypedData', $namespace = NULL) {
    $definition = UnitTestHelpers::getPluginDefinition($class, $plugin);
    if (!isset($definition['list_class'])) {
      $definition['list_class'] = 'Drupal\Core\Field\FieldItemList';
    }
    $this->stubPluginsDefinition[self::getIdWithNamespace($definition['id'], $namespace)] = $definition;
  }

  public function stubSetDefinition($definition, $namespace = NULL, $customId = NULL) {
    $this->stubPluginsDefinition[self::getIdWithNamespace($customId ?? $definition['id'], $namespace)] = $definition;
  }

  public function stubSetDefinitionFromClass(string $class, $plugin = 'TypedData', $namespace = NULL) {
    $definition = UnitTestHelpers::getPluginDefinition($class, $plugin);
    self::stubSetDefinition($definition, $plugin, $namespace);
  }

  private function getIdWithNamespace(string $id, string $namespace = NULL) {
    return $namespace
      ? $namespace . ':' . $id
      : $id;
  }

}
