<?php

namespace Drupal\Tests\test_helpers_test\Unit;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Parser;

/**
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers_example
 */
class InitServiceTest extends UnitTestCase {

  /**
   * @covers ::service
   */
  public function testInitServiceOtherNamespace() {
    // Explicitly pass the services file.
    $service = TestHelpers::service(
      Parser::class,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      dirname(__FILE__) . '/../../../test_helpers_test.services.yml'
    );
    $this->assertInstanceOf(Parser::class, $service);

    // Auto detects the module.
    $service2 = TestHelpers::service(
      Parser::class,
    );
    $this->assertInstanceOf(Parser::class, $service2);
  }

}
