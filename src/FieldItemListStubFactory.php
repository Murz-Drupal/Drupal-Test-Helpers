<?php

namespace Drupal\test_helpers;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\test_helpers\Stub\ItemStub;

/**
 * The FieldItemListStubFactory class.
 */
class FieldItemListStubFactory {

  /**
   * The Field Type Manager stub.
   *
   * @var \Drupal\test_helpers\FieldTypeManagerStub
   */
  protected $fieldTypeManagerStub;

  /**
   * Constructs a new EntityStubFactory.
   */
  public function __construct() {
    // Reusing a string field type definition as default one.
    // $stringItemDefinition = UnitTestHelpers::getPluginDefinition(StringItem::class, 'Field', '\Drupal\Core\Field\Annotation\FieldType');
    // $this->fieldTypeManagerStub->addDefinition('string', $stringItemDefinition);.
  }

  /**
   * Creates a field definition stub.
   *
   * @param string $class
   *   Field class.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   A field field definition stub.
   */
  public static function createFieldItemDefinitionStub(string $class = NULL): FieldDefinitionInterface {
    if (!$class) {
      // $class = StringItem::class;
      $class = ItemStub::class;
    }
    $definition = UnitTestHelpers::getPluginDefinition($class, 'Field', '\Drupal\Core\Field\Annotation\FieldType');
    // @todo Now it's a quick initialization of BaseFieldDefinition,
    // will be good to add support for other field types.
    $field_definition = BaseFieldDefinition::create($definition['id']);
    $field_definition->getItemDefinition()->setClass($class);
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
      $definition = self::createFieldItemDefinitionStub(ItemStub::class);
      $definition->setName($name);
    }
    $field = new FieldItemList($definition, $name, $parent);
    $field = UnitTestHelpers::createPartialMockWithConstructor(FieldItemList::class,
      [
        'applyDefaultValue',
      ],
      [$definition, $name, $parent]
    );

    // We have no information about default values because of missing configs,
    // so just return the same object.
    UnitTestHelpers::bindClosureToClassMethod(
      function ($notify = TRUE) {
        return $this;
      },
      $field,
      'applyDefaultValue'
    );

    $field->setValue($values);

    return $field;
  }

}
