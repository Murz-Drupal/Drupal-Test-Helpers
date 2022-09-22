<?php

namespace Drupal\test_helpers;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use PHPUnit\Framework\TestCase;

/**
 * The FieldItemList Stub factory.
 */
class FieldItemListStubFactory extends TestCase {

  /**
   * The Field Type Manager stub.
   *
   * @var \Drupal\test_helpers\FieldTypeManagerStub
   */
  protected $fieldTypeManagerStub;

  /**
   * Constructs a new EntityStubFactory.
   *
   * @param \Drupal\test_helpers\FieldTypeManagerStub $fieldTypeManagerStub
   *   The Field Type Manager stub to use.
   */
  public function __construct(FieldTypeManagerStub $fieldTypeManagerStub) {
    $this->fieldTypeManagerStub = $fieldTypeManagerStub;
  }

  /**
   * Creates a field definition stub.
   *
   * @param string $name
   *   Field name.
   * @param string $type
   *   Type of the creating field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   A field field definition stub.
   */
  private function createFieldItemDefinitionStub(string $name, string $type): FieldDefinitionInterface {
    // @todo Now it's a quick initialization of BaseFieldDefinition,
    // will be good to add support for other field types.
    $field_definition = BaseFieldDefinition::create($type);
    $field_definition->setName($name);
    $field_definition->getItemDefinition()->setClass(StringItem::class);
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
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   A field item list with items as stubs.
   */
  public function create(string $name, $values = NULL, FieldDefinitionInterface $definition = NULL): FieldItemListInterface {
    if (!$definition) {
      // @todo Now it's a hard-coded type, will be good to add support for
      // custom types.
      $type = 'string';
      $definition = $this->createFieldItemDefinitionStub($name, $type);
    }
    $field = new FieldItemList($definition, $name);
    $field->setValue($values);

    return $field;
  }

}
