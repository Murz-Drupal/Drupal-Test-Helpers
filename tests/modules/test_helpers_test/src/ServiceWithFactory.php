<?php

namespace Drupal\test_helpers_test;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * A ServiceWithFactory service.
 */
class ServiceWithFactory {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    public MessengerInterface $messenger,
  ) {
  }

}
