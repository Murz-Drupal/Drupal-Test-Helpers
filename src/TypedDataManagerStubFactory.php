<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Tests\UnitTestCase;

/**
 * The TypedDataManagerStubFactory class.
 */
class TypedDataManagerStubFactory extends UnitTestCase {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct() {
    UnitTestHelpers::addToContainer('entity_field.manager', $this->createMock(EntityFieldManagerInterface::class));
    UnitTestHelpers::addToContainer('entity_type.manager', (new EntityTypeManagerStubFactory())->create());
    UnitTestHelpers::addToContainer('entity.repository', $this->createMock(EntityRepositoryInterface::class));
  }

  /**
   * Creates an instance of TypedDataManagerStub.
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
