<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A stub of the Drupal's default ConfigFactory class.
 *
 * Validates any context names by default, until they are not defined by the
 * function stubAddContext().
 */
class CacheContextsManagerStub extends CacheContextsManager {

  /**
   * Constructs a CacheContextsManager object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param string[] $contexts
   *   An array of the available cache context IDs, NULL to accept all contexts.
   */
  public function __construct(ContainerInterface $container = NULL, array $contexts = NULL) {
    $container ??= TestHelpers::getContainer();
    parent::__construct($container, $contexts ?? []);
    $this->contexts = $contexts ?? TRUE;
  }

  /**
   * Adds contexts to the valid list.
   *
   * @param string|string[] $contexts
   *   The list of valid contexts.
   */
  public function stubAddContexts($contexts) {
    if ($this->contexts === TRUE) {
      $this->contexts = [];
    }
    if (is_string($contexts)) {
      $contexts = [$contexts];
    }
    $this->contexts = array_merge($this->contexts, $contexts);
    unset($this->validContextTokens);
  }

  /**
   * Sets the full list of contexts.
   *
   * @param string[] $contexts
   *   The list of valid contexts.
   */
  public function stubSetContexts(array $contexts) {
    $this->contexts = $contexts;
    unset($this->validContextTokens);
  }

  /**
   * {@inheritDoc}
   */
  public function assertValidTokens($context_tokens) {
    if ($this->contexts === TRUE) {
      return TRUE;
    }
    return parent::assertValidTokens($context_tokens);
  }

}
