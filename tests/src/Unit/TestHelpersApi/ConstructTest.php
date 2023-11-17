<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Construct function.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class ConstructTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    try {
      // @phpstan-ignore-next-line We're testing the exception.
      new TestHelpers();
      $this->fail('The __construct method should be private.');
    }
    catch (\Throwable $e) {
      $this->assertEquals(0, $e->getCode());
    }
  }

}
