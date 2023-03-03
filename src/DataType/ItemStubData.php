<?php

namespace Drupal\test_helpers\DataType;

use Drupal\Core\TypedData\PrimitiveBase;

/**
 * The item stub data type.
 *
 * The plain value of a string is a regular PHP string. For setting the value
 * any PHP variable that casts to a string may be passed.
 *
 * @DataType(
 *   id = "item_stub",
 *   label = @Translation("Item stub")
 * )
 */
class ItemStubData extends PrimitiveBase {

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getString();
  }

}
