<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Parser;

/**
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class ServicesNonamedTest extends UnitTestCase {

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
      dirname(__FILE__) . '/../../../../modules/test_helpers_example/test_helpers_example.services.yml'
    );
    $this->assertInstanceOf(Parser::class, $service);

    // Auto detection test is implemented in the submodule test
    // \Drupal\Tests\test_helpers_example\Unit\InternalTests\InitServiceTest.
  }

}
