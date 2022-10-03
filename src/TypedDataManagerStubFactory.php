<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * The TypedDataManagerStubFactory class.
 */
class TypedDataManagerStubFactory {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct() {
    $this->unitTestCaseApi = UnitTestCaseApi::getInstance();
    UnitTestHelpers::addToContainer('entity_field.manager', $this->unitTestCaseApi->createMock(EntityFieldManagerInterface::class));
    UnitTestHelpers::addToContainer('entity_type.manager', (new EntityTypeManagerStubFactory())->create());
    UnitTestHelpers::addToContainer('entity.repository', $this->unitTestCaseApi->createMock(EntityRepositoryInterface::class));
  }

  /**
   * Creates an instance of TypedDataManagerStub.
   */
  public function createInstance() {
    /** @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit\Framework\MockObject\MockObject $instance */
    $instance = $this->unitTestCaseApi->createPartialMock(TypedDataManager::class, [
      'getDefinition',
      'stubAddPlugin',
      'stubAddDefinition',
    ]);
    UnitTestHelpers::bindClosureToClassMethod(
      function ($plugin_id, $exception_on_invalid = TRUE) {
        return $this->stubPluginsDefinition[$plugin_id] ?? NULL;
      },
      $instance,
      'getDefinition'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      // @todo Check if $namespace is a correct term here.
      function ($class, $plugin = 'TypedData', $namespace = NULL) {
        $definition = UnitTestHelpers::getPluginDefinition($class, $plugin);
        if ($namespace) {
          $this->stubPluginsDefinition[$namespace . ':' . $definition['id']] = $definition;
        }
        else {
          $this->stubPluginsDefinition[$definition['id']] = $definition;
        }
      },
      $instance,
      'stubAddPlugin'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      // @todo Check if $namespace is a correct term here.
      function (string $id, $definition) {
          $this->stubPluginsDefinition[$id] = $definition;
      },
      $instance,
      'stubAddDefinition'
    );

    $instance->stubAddPlugin(StringData::class);
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
    return $instance;
  }

}
