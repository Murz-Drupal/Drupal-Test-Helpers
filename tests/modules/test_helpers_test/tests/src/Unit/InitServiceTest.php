<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers_test\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\Yaml\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Test Helpers API, related to services in other namespaces.
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers_example')]
#[CoversMethod(TestHelpers::class, 'service')]
class InitServiceTest extends UnitTestCase {

  /**
   * Tests the service() method with a service from another namespace.
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
