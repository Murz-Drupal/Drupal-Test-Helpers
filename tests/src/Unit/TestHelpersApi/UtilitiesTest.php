<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests utility functions.
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
#[CoversMethod(TestHelpers::class, 'addIteratorToMock')]
class UtilitiesTest extends UnitTestCase {

  /**
   * Tests the addIteratorToMock function.
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
