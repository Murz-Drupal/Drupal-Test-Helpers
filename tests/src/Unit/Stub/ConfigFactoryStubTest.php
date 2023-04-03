<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\test_helpers\TestHelpers;
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
    TestHelpers::getContainer();
    $configFactory = TestHelpers::service('config.factory');
    $config1 = [
      'foo' => 1,
      'bar' => 42,
    ];
    $config2 = ['foo2'];

    $configFactory->stubSetConfig('config1', $config1);
    $configFactory->stubSetConfig('config.two', $config2);

    $config2Immutable = $configFactory->get('config.two');
    $this->assertInstanceOf(ImmutableConfig::class, $config2Immutable);
    $this->assertEquals($config2, $config2Immutable->get());

    $config2Editable = $configFactory->getEditable('config.two');
    $this->assertInstanceOf(Config::class, $config2Editable);
    $this->assertEquals($config2, $config2Editable->get());
    $config2Editable->set('foo', 2);
    $config2Editable->save();
    $this->assertEquals(2, $configFactory->get('config.two')->get('foo'));

    $this->assertEquals($config1['foo'], $configFactory->get('config1')->get('foo'));
    $this->assertEquals($config1['foo'], $configFactory->getEditable('config1')->get('foo'));

    $configFactory->stubSetConfig('config.two', ['foo3']);
    $this->assertEquals(['foo3'], $configFactory->get('config.two')->get());


  }

}
