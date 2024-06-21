<?php

namespace Drupal\test_helpers\Stub;

/**
 * A trait to workaround D10 breaking changes in the interface
 */
trait ContainerAwareEventDispatcherStubTrait {

  /**
   * {@inheritdoc}
   */
  public function dispatch($event): object {
    return $this->doDispatch($event, NULL);
  }

}
