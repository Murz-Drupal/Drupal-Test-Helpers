<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default Token class.
 */
class TokenStub extends Token {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ModuleHandlerInterface $module_handler = NULL,
    CacheBackendInterface $cache = NULL,
    LanguageManagerInterface $language_manager = NULL,
    CacheTagsInvalidatorInterface $cache_tags_invalidator = NULL,
    RendererInterface $renderer = NULL
  ) {
    $this->cache = $cache;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler ?? TestHelpers::service('module_handler');
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->renderer = $renderer ?? TestHelpers::createMock(RendererInterface::class);
  }

}
