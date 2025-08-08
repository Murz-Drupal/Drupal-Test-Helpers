<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\Plugin\Field\FieldType\ItemStubItem;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests ItemStubItem class.
 */
#[CoversClass(ItemStubItem::class)]
#[Group('test_helpers')]
#[CoversMethod(ItemStubItem::class, 'schema')]
#[CoversMethod(ItemStubItem::class, 'generateSampleValue')]
class ItemStubItemTest extends UnitTestCase {

  /**
   * Tests the schema method of ItemStubItem.
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
   * Tests generateSampleValue() function.
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
