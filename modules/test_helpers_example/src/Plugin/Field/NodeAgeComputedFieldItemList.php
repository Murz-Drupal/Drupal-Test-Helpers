<?php

namespace Drupal\test_helpers_example\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed field calculating node age.
 */
class NodeAgeComputedFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $node = $this->getEntity(); /** @var \Drupal\node\NodeInterface $node */
    $node_age = \Drupal::time()->getCurrentTime() - $node->getCreatedTime();
    $this->list[0] = $this->createItem(0, $node_age);
  }

}
