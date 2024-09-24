<?php

namespace Drupal\test_helpers_functional\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Fills the stored env variables values on the Drupal Kernel boot.
 */
class TestHelpersFunctionalEventSubscriber implements EventSubscriberInterface {

  const STATE_KEY_ENV_VARIABLES = "test_helpers_functional.env_variables";

  /**
   * The constructor.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  public function __construct(
    protected StateInterface $state,
  ) {
  }

  /**
   * Fills the environment variables from the state.
   */
  public function onRequest(): void {
    $envVariables = $this->state->get(self::STATE_KEY_ENV_VARIABLES, []);
    foreach ($envVariables as $key => $value) {
      putenv("$key=$value");
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 1000],
      KernelEvents::VIEW => ['onRequest', 1000],
    ];
  }

}
