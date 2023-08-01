<?php

namespace Drupal\Tests\test_helpers\Unit\Stubs;

use Drupal\comment\Plugin\Field\FieldType\CommentItem;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\StubFactory\FieldItemListStubFactory;
use Drupal\test_helpers\TestHelpers;

/**
 * Tests FieldItemListStubFactory class.
 *
 * @coversDefaultClass \Drupal\test_helpers\StubFactory\FieldItemListStubFactory
 * @group test_helpers
 */
class FieldItemListStubFactoryTest extends UnitTestCase {

  /**
   * @covers ::create
   * @covers ::createFieldItemDefinitionStub
   * @covers \Drupal\test_helpers\TestHelpers::addFieldPlugin
   * @covers \Drupal\test_helpers\TestHelpers::createFieldStub
   */
  public function testGeneralApi() {
    $field = TestHelpers::createFieldStub();
    $this->assertEquals('field_item:item_stub', $field->getItemDefinition()->getDataType());

    $field = TestHelpers::createFieldStub(NULL, $type = 'string');
    $this->assertEquals("field_item:$type", $field->getItemDefinition()->getDataType());
    $this->assertEquals([], $field->getValue());
    $this->assertNull($field->getName());
    $this->assertNull($field->getParent());
    $this->assertFalse($field->getFieldDefinition()->isBaseField());

    $parent = EntityAdapter::createFromEntity(TestHelpers::createEntity('node'));
    $field = TestHelpers::createFieldStub($value = 'mail@example.com', $type = 'email', $name = 'field_mail', $parent, TRUE);
    $this->assertEquals("field_item:$type", $field->getItemDefinition()->getDataType());
    $this->assertEquals([['value' => $value]], $field->getValue());
    $this->assertEquals($name, $field->getName());
    $this->assertEquals($parent, $field->getParent());
    $this->assertTrue($field->getFieldDefinition()->isBaseField());

    $field = TestHelpers::createFieldStub($value = ['1', 2], $type = 'integer');
    $this->assertEquals("field_item:$type", $field->getItemDefinition()->getDataType());
    $this->assertEquals([['value' => '1'], ['value' => 2]], $field->getValue());

    $field = TestHelpers::createFieldStub(['value' => TRUE], BooleanItem::class);
    $this->assertEquals('field_item:boolean', $field->getItemDefinition()->getDataType());
    $this->assertEquals([['value' => TRUE]], $field->getValue());

    TestHelpers::assertException(function () {
      TestHelpers::createFieldStub(NULL, 'comment');
    });
    TestHelpers::addFieldPlugin(CommentItem::class);
    $field = TestHelpers::createFieldStub(1, 'comment');
    $this->assertEquals('field_item:comment', $field->getItemDefinition()->getDataType());
    $this->assertEquals([['status' => 1]], $field->getValue());
    $field = TestHelpers::createFieldStub(['last_comment_name' => 'Bob'], CommentItem::class);
    $this->assertEquals([['last_comment_name' => 'Bob']], $field->getValue());

    $definition = FieldItemListStubFactory::createFieldItemDefinitionStub(MapItem::class);
    $field = TestHelpers::createFieldStub([], $definition);
    $this->assertEquals('field_item:map', $field->getItemDefinition()->getDataType());
    $this->assertEquals([], $field->getValue());

    $field = TestHelpers::createFieldStub([], 'map');
    $this->assertEquals('field_item:map', $field->getItemDefinition()->getDataType());
    $this->assertEquals([], $field->getValue());

    // Testing mocked methods.
    $field = TestHelpers::createFieldStub([], 'map', NULL, NULL, NULL,
      ['generateSampleItems'],
    );
    $field->method('generateSampleItems')->willReturn('foo');
  }

}
