<?php

namespace Drupal\test_helpers\Stub;

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
    $this->stubAddPlugin(StringData::class);
    /* @todo Try to register popular definitions via something like:
     * $instance->stubAddPlugin(StringData::class);
     * $instance->stubAddPlugin(EntityAdapter::class);
     * $instance->stubAddPlugin(EntityReferenceItem::class);
     * $instance->stubAddPlugin(StringData::class, 'TypedData', 'field_item');
     * $instance->stubAddPlugin(StringLongItem::class, 'Field', 'field_item');
     * $definition = UnitTestHelpers::getPluginDefinition(StringLongItem::class, 'Field');
     * $instance->stubAddDefinition('field_item:' . $definition['id'], $definition);
     * $definition = UnitTestHelpers::getPluginDefinition(BooleanItem::class, 'Field');
     * $instance->stubAddDefinition('field_item:' . $definition['id'], $definition);
     */
  }

  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->stubPluginsDefinition[$plugin_id] ?? NULL;
  }

  public function stubAddPlugin($class, $plugin = 'TypedData', $namespace = NULL) {
    $definition = UnitTestHelpers::getPluginDefinition($class, $plugin);
    if ($namespace) {
      $this->stubPluginsDefinition[$namespace . ':' . $definition['id']] = $definition;
    }
    else {
      $this->stubPluginsDefinition[$definition['id']] = $definition;
    }
  }

  public function stubAddDefinition(string $id, $definition) {
    $this->stubPluginsDefinition[$id] = $definition;
  }

}
