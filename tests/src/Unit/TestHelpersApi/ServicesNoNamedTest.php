<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\Yaml\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for services without explicit names.
 *
 * @covers TestHelpers::service
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
class ServicesNoNamedTest extends UnitTestCase {

  /**
   * Tests the service() method with a service from another namespace.
   */
  public function testInitServiceOtherNamespace() {
    if (version_compare(\Drupal::VERSION, '10.0', '<')) {
      $this->markTestSkipped('This test is skipped for Drupal versions lower than 10.0.');
    }
    // Explicitly pass the services file.
    $service = TestHelpers::service(
      Parser::class,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      dirname(__FILE__) . '/../../../../tests/modules/test_helpers_test/test_helpers_test.services.yml'
    );
    $this->assertInstanceOf(Parser::class, $service);

    // Auto detection test is implemented in the submodule test
    // \Drupal\Tests\test_helpers_example\Unit\InternalTests\InitServiceTest.
  }

}
