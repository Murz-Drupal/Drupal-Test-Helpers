<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A stub of the Drupal's default ConfigFactory class.
 *
 * Validates any context names by default, until they are not defined by the
 * function stubAddContext().
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class CacheContextsManagerStub extends CacheContextsManager {

  /**
   * A flag to accept all contexts by default, if no contexts is set manually.
   *
   * @var bool
   */
  protected $stubAllowAnyContexts;

  /**
   * Constructs a CacheContextsManager object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param string[] $contexts
   *   An array of the available cache context IDs, NULL to accept all contexts.
   */
  public function __construct(ContainerInterface $container, array $contexts = []) {
    parent::__construct($container, $contexts ?? []);
    $this->stubAllowAnyContexts = TRUE;
  }

  /**
   * Adds contexts to the valid list.
   *
   * @param string|string[] $contexts
   *   The list of valid contexts.
   */
  public function stubAddContexts($contexts) {
    $this->stubAllowAnyContexts = FALSE;
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
    $this->stubAllowAnyContexts = FALSE;
    $this->contexts = $contexts;
    unset($this->validContextTokens);
  }

  /**
   * {@inheritdoc}
   */
  public function assertValidTokens($context_tokens) {
    if ($this->stubAllowAnyContexts === TRUE) {
      return TRUE;
    }
    return parent::assertValidTokens($context_tokens);
  }

}
