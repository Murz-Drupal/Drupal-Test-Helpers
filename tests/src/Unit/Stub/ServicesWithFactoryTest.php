<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Messenger\Messenger;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests TestHelpers::initServiceFromYaml() function with factory.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class ServicesWithFactoryTest extends UnitTestCase {

  /**
   * @covers ::initServiceFromYaml
   */
  public function testService() {
    $yaml = __DIR__ . '/../../../../modules/test_helpers_test/test_helpers_test.services.yml';
    $service = TestHelpers::initServiceFromYaml($yaml, 'test_helpers_test.service_with_factory');
    $this->assertInstanceOf(Messenger::class, $service->messenger);
  }

}
