<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\ConfigFactoryStub;
use Drupal\test_helpers\UnitTestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\ConfigFactoryStub
 * @group test_helpers
 */
class ConfigFactoryStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::stubSetConfig
   */
  public function testSelect() {
    UnitTestHelpers::getContainer();
    $configFactory = new ConfigFactoryStub();
    $config1 = [
      'foo' => 1,
      'bar' => 42,
    ];
    $config2 = ['foo2'];

    $configFactory->stubSetConfig('config1', $config1);
    $configFactory->stubSetConfig('config.two', $config2);

    $this->assertEquals($config2, $configFactory->get('config.two')->get());
    $this->assertEquals($config1['foo'], $configFactory->get('config1')->get('foo'));
  }

}
