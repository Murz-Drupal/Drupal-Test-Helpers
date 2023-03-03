<?php

namespace Drupal\test_helpers\StubFactory;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
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
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   A field field definition stub.
   */
  public static function createFieldItemDefinitionStub(string $class = NULL, array $settings = NULL): FieldDefinitionInterface {
    if (!$class) {
      $class = ItemStubItem::class;
    }
    $definition = TestHelpers::getPluginDefinition($class, 'Field');
    // @todo Now it's a quick initialization of BaseFieldDefinition,
    // will be good to add support for other field types.
    $field_definition = BaseFieldDefinition::create($definition['id']);
    $field_definition->getItemDefinition()->setClass($class);
    if ($settings) {
      $field_definition->setSettings($settings);
    }
    return $field_definition;
  }

  /**
   * Creates an entity type stub and defines a static storage for it.
   *
   * @param string $name
   *   Field name.
   * @param mixed $values
   *   Field values array.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   Definition to use, will use BaseFieldDefinition if not passed.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   Parent item for attaching to the field.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   A field item list with items as stubs.
   */
  public static function create(string $name, $values = NULL, FieldDefinitionInterface $definition = NULL, TypedDataInterface $parent = NULL): FieldItemListInterface {
    if (!$definition) {
      // @todo Now it's a hard-coded type, will be good to add support for
      // custom types.
      $definition = self::createFieldItemDefinitionStub(ItemStubItem::class);
      $definition->setName($name);
    }
    $field = new FieldItemList($definition, $name, $parent);

    $field->setValue($values);
    foreach ($field as $fieldItem) {
      $fieldItem->setContext($name, $parent);
    }
    return $field;
  }

}
