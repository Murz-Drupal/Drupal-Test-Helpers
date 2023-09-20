<?php

namespace Drupal\test_helpers\StubFactory;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\test_helpers\Plugin\Field\FieldType\ItemStubItem;
use Drupal\test_helpers\TestHelpers;

/**
 * The FieldItemListStubFactory class.
 */
class FieldItemListStubFactory {

  /**
   * Constructs a new EntityStubFactory.
   */
  public function __construct() {
  }

  /**
   * Creates a field definition stub.
   *
   * @param string $class
   *   Field class.
   * @param array $settings
   *   Field settings.
   * @param bool $isBaseField
   *   A flag to create a base field instance.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   A field field definition stub.
   */
  public static function createFieldItemDefinitionStub(string $class = NULL, array $settings = NULL, bool $isBaseField = NULL): FieldDefinitionInterface {
    if (!$class) {
      $class = ItemStubItem::class;
    }
    $definition = TestHelpers::getPluginDefinition($class, 'Field');
    // @todo Rework when https://www.drupal.org/node/2280639 lands.
    $definitionFactory = $isBaseField ? BaseFieldDefinition::class : FieldStorageDefinition::class;
    $field_definition = $definitionFactory::create($definition['id']);
    $field_definition->getItemDefinition()->setClass($class);
    if ($settings) {
      $field_definition->setSettings($settings);
    }
    return $field_definition;
  }

  /**
   * Creates a field instance stub.
   *
   * @param string|null $name
   *   The field name.
   * @param array|string|null $values
   *   The field values.
   * @param string|\Drupal\Core\Field\FieldDefinitionInterface|null $typeOrDefinition
   *   A field type like 'string', 'integer', 'boolean'.
   *   Or a path to a field class like
   *   Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem.
   *   Or a ready definition object to use.
   *   If null - will be created a stub with fallback ItemStubItem definition.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   Parent item for attaching to the field.
   * @param bool|null $isBaseField
   *   A flag to create a base field instance.
   * @param array|null $mockMethods
   *   A list of method to mock when creating the instance.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   A field item list with items as stubs.
   */
  public static function create(
    string $name = NULL,
    $values = NULL,
    $typeOrDefinition = NULL,
    TypedDataInterface $parent = NULL,
    bool $isBaseField = NULL,
    array $mockMethods = NULL
  ): FieldItemListInterface {
    TestHelpers::initEntityTypeManagerStubs();
    if (is_string($typeOrDefinition)) {
      if (strpos($typeOrDefinition, '\\') !== FALSE) {
        $class = $typeOrDefinition;
        $itemDefinitionArray = TestHelpers::getPluginDefinition($class, 'Field');
        $plugin = 'field_item:' . $itemDefinitionArray['id'];
      }
      else {
        $plugin = 'field_item:' . $typeOrDefinition;
      }
      $itemDefinitionArray ??= TestHelpers::service('typed_data_manager')->getDefinition($plugin);

      // @todo Rework when https://www.drupal.org/node/2280639 lands.
      $definitionFactory = $isBaseField
        ? BaseFieldDefinition::class
        : FieldStorageDefinition::class;
      $definition = $definitionFactory::create($itemDefinitionArray['id']);
    }
    elseif ($typeOrDefinition instanceof FieldDefinitionInterface) {
      $definition = $typeOrDefinition;
    }
    else {
      $definition = self::createFieldItemDefinitionStub();
    }
    if ($name) {
      $definition->setName($name);
    }
    if (empty($mockMethods)) {
      $field = new FieldItemList($definition, $name, $parent);
    }
    else {
      $field = TestHelpers::createPartialMockWithConstructor(
        FieldItemList::class,
        $mockMethods,
        [$definition, $name, $parent],
      );
    }
    $field->setValue($values);
    foreach ($field as $fieldItem) {
      $fieldItem->setContext($name, $parent);
    }
    return $field;
  }

}
