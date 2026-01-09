<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Construct function.
 *
 * @covers TestHelpers::__construct
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
class ConstructTest extends UnitTestCase {

  /**
   * Tests the __construct() method.
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
