<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * A stub of Drupal's default ContainerAwareEventDispatcher class.
 *
 * @package Drupal\test_helpers\Stub
 */
class ContainerAwareEventDispatcherStub extends ContainerAwareEventDispatcher {

  /**
   * The array of dispatched events per event name.
   *
   * @var array
   */
  protected array $stubDispatchedEvents = [];

  /**
   * {@inheritdoc}
   */
  public function dispatch(object $event, ?string $eventName = NULL): object {
    $event_name = $eventName ?? get_class($event);
    $this->stubDispatchedEvents[$event_name][] = $event;
    return parent::dispatch($event, $eventName);
  }

  /**
   * Get the array of dispatched events.
   *
   * @return array
   *   The array of dispatched events per event name.
   */
  public function stubGetDispatchedEvents(): array {
    return $this->stubDispatchedEvents;
  }

}
