<?php

namespace Drupal\Tests\test_helpers_example\Unit;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\test_helpers\TestHelpers;
use Drupal\test_helpers_example\Plugin\Field\NodeAgeComputedFieldItemList;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\test_helpers_example\ArticlesManagerService
 * @group test_helpers_example
 */
class NodeAgeComputedFieldItemListTest extends UnitTestCase {

  /**
   * Tests Test Helpers API, related to entities.
   */
  public function testComputeValue() {
    $timeCreated = 500;
    $currentTime = 1000;
    TestHelpers::service('datetime.time', NULL, NULL, ['getCurrentTime'])
      ->method('getCurrentTime')->willReturnCallback(
        function () use (&$currentTime) {
          return $currentTime;
        }
      );
    $node = TestHelpers::createEntity(Node::class, ['created' => $timeCreated]);
    $fieldItemList = TestHelpers::createPartialMockWithConstructor(
      NodeAgeComputedFieldItemList::class,
      ['createItem'],
      [
        $this->createMock(DataDefinitionInterface::class),
        'field_node_age',
        EntityAdapter::createFromEntity($node),
      ],
    );
    $fieldItemList->method('createItem')->willReturnCallback(
      function ($offset = 0, $value = NULL) use (&$currentTime, &$timeCreated) {
        $this->assertEquals($currentTime - $timeCreated, $value);
      }
    );
    TestHelpers::callPrivateMethod($fieldItemList, 'computeValue');
    $currentTime = 2000;
    TestHelpers::callPrivateMethod($fieldItemList, 'computeValue');
  }

}
