<?php

namespace Drupal\test_helpers;

@trigger_error('Class UnitTestHelpers is deprecated in test_helpers:1.0.0-beta5 and is removed from test_helpers:1.0.0-rc1. Renamed to TestHelpers. See https://www.drupal.org/project/test_helpers/issues/3337449', E_USER_DEPRECATED);

/**
 * Helper functions to simplify writing of Unit Tests.
 *
 * @deprecated in test_helpers:1.0.0-beta5 and is removed from
 *   test_helpers:1.0.0-rc1. Renamed to TestHelpers().
 * @see https://www.drupal.org/project/test_helpers/issues/3337449
 */
class UnitTestHelpers extends TestHelpers {

}
