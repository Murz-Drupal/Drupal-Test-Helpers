<?php

namespace Drupal\test_helpers\Stub;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * A stub of Drupal's default ContainerAwareEventDispatcher class.
 *
 * @package Drupal\test_helpers\Stub
 */
class ContainerAwareEventDispatcherStub extends EventDispatcher {

  /**
   * The array of dispatched events per event name.
   *
   * @var array
   */
  protected array $stubDispatchedEvents = [];

  /**
   * A proxy function to workaround D10 breaking changes in the interface.
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
