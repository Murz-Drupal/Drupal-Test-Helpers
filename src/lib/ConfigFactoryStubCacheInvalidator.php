<?php

namespace Drupal\test_helpers\lib;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\test_helpers\TestHelpers;

/**
 * Passes cache tag events to classes that wish to respond to them.
 */
class ConfigFactoryStubCacheInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($tags as $tag) {
      $parts = explode(':', $tag);
      if ($parts[0] != 'config') {
        continue;
      }
      TestHelpers::service('config.factory')->reset($parts[1] ?? NULL);
    }
  }

}
