<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Site\Settings;

/**
 * A factory for the Drupal's Settings for tests.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class SettingsStubFactory {

  public static function get(array $settings = []) {
    // Setting the default cache to the memory backend.
    if ($settings['cache']['default'] ?? NULL === NULL) {
      $settings['cache']['default'] = 'cache.backend.memory';
    }

    static $settingsInstance;
    $settingsInstance ??= new Settings($settings);
    return $settingsInstance;
  }

}
