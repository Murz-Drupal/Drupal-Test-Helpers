<?php

namespace Drupal\test_helpers;

use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Tests\UnitTestCase;

/**
 * The Entity Storage Stub class.
 */
class TypedDataManagerStubFactory extends UnitTestCase {

  /**
   * Creates an entity type stub and defines a static storage for it.
   */
  public function createInstance() {
    /** @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit\Framework\MockObject\MockObject $instance */
    $instance = $this->createPartialMock(TypedDataManager::class, [
      'getDefinition',
      'stubAddPlugin',
    ]);
    UnitTestHelpers::bindClosureToClassMethod(
      function ($plugin_id, $exception_on_invalid = TRUE) {
        return $this->stubPluginsDefinition[$plugin_id] ?? NULL;
      },
      $instance,
      'getDefinition'
    );

    UnitTestHelpers::bindClosureToClassMethod(
      function ($class) {
        $definition = UnitTestHelpers::getPluginDefinition($class, 'TypedData');
        $this->stubPluginsDefinition[$definition['id']] = $definition;
      },
      $instance,
      'stubAddPlugin'
    );
    $instance->stubAddPlugin(StringData::class);

    return $instance;
  }

}
