<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Messenger\Messenger;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests TestHelpers::initServiceFromYaml() function with factory.
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
#[CoversMethod(TestHelpers::class, 'initServiceFromYaml')]
class ServicesWithFactoryTest extends UnitTestCase {

  /**
   * Tests the service initialization from YAML with a factory.
   */
  public function testService() {
    $yaml = __DIR__ . '/../../../../tests/modules/test_helpers_test/test_helpers_test.services.yml';
    $service = TestHelpers::initServiceFromYaml($yaml, 'test_helpers_test.service_with_factory');
    $this->assertInstanceOf(Messenger::class, $service->messenger);
    $serviceNamed = TestHelpers::initServiceFromYaml($yaml, 'test_helpers_test.service_with_factory.named');
    $this->assertInstanceOf(Messenger::class, $serviceNamed->messenger);
  }

}
