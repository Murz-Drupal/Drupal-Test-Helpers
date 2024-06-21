<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;

// A workaround to make the logger compatible with Drupal 9.x and 10.x together.
if (version_compare(\Drupal::VERSION, '10.0.0') <= 0) {
  require_once __DIR__ . '/ContainerAwareEventDispatcherStubTrait.D9.inc';
}
else {
  require_once __DIR__ . '/ContainerAwareEventDispatcherStubTrait.D10.inc';
}

/**
 * A stub of Drupal's default ContainerAwareEventDispatcher class.
 *
 * @package Drupal\test_helpers\Stub
 */
class ContainerAwareEventDispatcherStub extends ContainerAwareEventDispatcher {

  use ContainerAwareEventDispatcherStubTrait;

  /**
   * The array of dispatched events per event name.
   *
   * @var array
   */
  protected array $stubDispatchedEvents = [];

  /**
   * A proxy function to workaround D10 breaking changes in the interface.
   */
  public function doDispatch(object $event, ?string $eventName = NULL): object {
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
