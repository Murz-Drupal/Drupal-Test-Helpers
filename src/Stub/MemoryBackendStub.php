<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\MemoryCache\MemoryCache;

/**
 * A stub of the Drupal's default ConfigurableLanguageManager class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class MemoryBackendStub extends MemoryCache {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // @todo Rework this to a better way.
    // A workaround for the new MemoryCache instance in Drupal 10.3, which
    // requires the time parameter.
    if (method_exists(MemoryCache::class, '__construct')) {
      $time = new Time();
      // @phpstan-ignore-next-line
      parent::__construct($time);
    }
  }

}
