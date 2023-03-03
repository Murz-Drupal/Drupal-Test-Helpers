<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Messenger\Messenger;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * The messenger service.
 */
class MessengerStub extends Messenger {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    FlashBagInterface $flash_bag = NULL,
    KillSwitch $killSwitch = NULL
  ) {
    $flash_bag ??= new FlashBag();
    $killSwitch ??= new KillSwitch();
    parent::__construct($flash_bag, $killSwitch);
  }

}
