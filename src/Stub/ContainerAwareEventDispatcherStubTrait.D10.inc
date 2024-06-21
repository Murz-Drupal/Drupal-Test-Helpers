<?php

namespace Drupal\test_helpers\Stub;

/**
 * A trait to workaround D10 breaking changes in the interface.
 */
trait ContainerAwareEventDispatcherStubTrait {

  /**
   * {@inheritdoc}
   */
  public function dispatch(object $event, ?string $eventName = NULL): object {
    return $this->doDispatch($event, $eventName);
  }

}
