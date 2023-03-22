<?php

namespace Drupal\test_helpers\includes;

/**
 * Utility functions.
 */
class Utils {

  /**
   * Checks if the actual version is equal or hiher than requested.
   *
   * @param string $version
   *   A version number to check in format like "10.1", "10", "9.5.3".
   */
  public static function isDrupalVersionEqualOrHigher(string $version): bool {
    $requested = explode('.', $version);
    if (!is_numeric($requested[0])) {
      throw new \Exception("Can't detect major version number from string \"$version\".");
    }
    $actual = explode('.', \DRUPAL::VERSION);
    if (
      $actual[0] < $requested[0]
      || (isset($requested[1]) && $actual[1] < $requested[1])
      || (isset($requested[2]) && $actual[2] < $requested[2])
    ) {
      return FALSE;
    }
    return TRUE;
  }

}
