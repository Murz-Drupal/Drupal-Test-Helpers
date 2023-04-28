<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\test_helpers\Plugin\Field\FieldType\ItemStubItem;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Plugin\Field\FieldType\ItemStubItem
 * @group test_helpers
 */
class ItemStubItemTest extends UnitTestCase {

  /**
   * @covers ::schema
   */
  public function testSchema() {
    $fieldDefinition = $this->createMock(FieldStorageDefinitionInterface::class);
    $fieldDefinition->method('getSetting')->willReturnMap([
      ['is_ascii', TRUE],
      ['max_length', 128],
      ['case_sensitive', TRUE],
    ]);
    $this->assertEquals([
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => 128,
          'binary' => TRUE,
        ],
      ],
    ], ItemStubItem::schema($fieldDefinition));

    TestHelpers::getMockedMethod($fieldDefinition, 'getSetting')
      ->willReturnMap([
        ['is_ascii', FALSE],
        ['max_length', 64],
        ['case_sensitive', FALSE],
      ]);
    $this->assertEquals([
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 64,
          'binary' => FALSE,
        ],
      ],
    ], ItemStubItem::schema($fieldDefinition));
  }

  /**
   * @covers ::generateSampleValue
   */
  public function testGenerateSampleValue() {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldDefinition->method('getSetting')->willReturnMap([
      ['max_length', 32],
    ]);
    $value = ItemStubItem::generateSampleValue($fieldDefinition);
    $this->assertIsString($value['value']);
    $this->assertLessThanOrEqual(32, strlen($value['value']));
  }

}
