<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\LoggerChannelFactoryStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\LoggerChannelFactoryStub
 * @group test_helpers
 */
class LoggerChannelFactoryStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::stubSetConfig
   */
  public function testApi() {
    $factory = new LoggerChannelFactoryStub();

    $this->assertEquals([], $factory->stubGetLogs());

    $context1 = [
      'uid' => '42',
    ];
    $channel1 = $factory->get('my_channel1');
    $channel1->warning('My message', $context1);
    $context2 = [
      'uid' => '53',
    ];
    $channel2 = $factory->get('my_channel2');
    $channel2->error('My error', $context2);

    $logs = $factory->stubGetLogs();

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($logs[0], [
      'uid' => '42',
      'type' => 'my_channel1',
      'message' => 'My message',
      'severity' => 4,
      'link' => '',
      'location' => '',
      'referer' => '',
      'hostname' => '',
      '_context' => [
        'uid' => '42',
        'channel' => 'my_channel1',
        'link' => '',
        'request_uri' => '',
        'referer' => '',
        'ip' => '',
      ],
    ]));
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($logs[1], [
      'uid' => '53',
      'type' => 'my_channel2',
      'message' => 'My error',
      'severity' => 3,
      'link' => '',
      'location' => '',
      'referer' => '',
      'hostname' => '',
      '_context' => [
        'uid' => '53',
        'channel' => 'my_channel2',
        'link' => '',
        'request_uri' => '',
        'referer' => '',
        'ip' => '',
      ],
    ]));
    $this->assertIsNumeric($logs[0]["timestamp"]);
    $this->assertIsNumeric($logs[1]["_context"]["timestamp"]);
    $this->assertGreaterThan($logs[0]["_microtime"], $logs[1]["_microtime"],);
  }

}
