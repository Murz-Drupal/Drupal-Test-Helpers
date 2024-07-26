<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Cache\CacheFactory;
use Drupal\Core\Site\Settings;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's core CacheFactory class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class CacheFactoryStub extends CacheFactory {

  /**
   * {@inheritdoc}
   */
  public function __construct(Settings $settings, array $default_bin_backends = []) {
    $cacheSettings = $settings->get('cache');
    if (isset($cacheSettings['default'])) {
      TestHelpers::service($cacheSettings['default']);
    }
    parent::__construct($settings, $default_bin_backends);
  }

}
