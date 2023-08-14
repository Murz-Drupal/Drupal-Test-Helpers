<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests utility functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class UtilitiesTest extends UnitTestCase {

  /**
   * @covers ::addIteratorToMock
   */
  public function testAddIteratorToMock() {
    $itemValues = ['foo', 'bar'];
    /** @var \ArrayIterator|\PHPUnit\Framework\MockObject\MockObject $items */
    $items = $this->createMock(FieldItemListInterface::class);
    TestHelpers::addIteratorToMock($itemValues, $items);
    $this->assertEquals('foo', $items[0]);
    $items->next();
    $this->assertEquals('bar', $items->current());
  }

}
