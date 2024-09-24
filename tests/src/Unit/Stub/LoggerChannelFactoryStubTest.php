<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;

/**
 * Tests LoggerChannelFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\LoggerChannelFactoryStub
 * @group test_helpers
 */
class LoggerChannelFactoryStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::stubGetLogs
   */
  public function testApi() {
    $factory = TestHelpers::service('logger.factory');

    $this->assertEquals([], $factory->stubGetLogs());

    $context1 = [
      'uid' => '41',
      'uid_custom' => '42',
    ];
    $channel1 = $factory->get('my_channel1');
    $channel1->warning('My message', $context1);
    $context2 = [
      'uid' => '53',
    ];

    $user2 = new UserSession(['uid' => 2]);
    TestHelpers::service('current_user')->setAccount($user2);
    $channel2 = $factory->get('my_channel2');
    $channel2->error('My error', $context2);

    $logs = $factory->stubGetLogs();

    // Seems the logger returns different results for Drupal 10.3 and earlier.
    $log0Expected = version_compare(\Drupal::VERSION, '10.3', '>=')
      ? [
        'uid' => 0,
        'type' => 'my_channel1',
        'message' => 'My message',
        'severity' => 4,
        'link' => '',
        'location' => TestHelpers::REQUEST_STUB_DEFAULT_URI,
        'referer' => '',
        'hostname' => '127.0.0.1',
        '_context' => [
          'uid' => 0,
          'uid_custom' => '42',
          'channel' => 'my_channel1',
          'link' => '',
          'request_uri' => TestHelpers::REQUEST_STUB_DEFAULT_URI,
          'referer' => '',
          'ip' => '127.0.0.1',
        ],
      ]
      : [
        'uid' => '41',
        'type' => 'my_channel1',
        'message' => 'My message',
        'severity' => 4,
        'link' => '',
        'location' => '',
        'referer' => '',
        'hostname' => '',
        '_context' => [
          'uid' => '41',
          'uid_custom' => '42',
          'channel' => 'my_channel1',
          'link' => '',
          'request_uri' => '',
          'referer' => '',
          'ip' => '',
        ],
      ];

    // Seems the logger returns different results for Drupal 10.3 and earlier.
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($logs[0], $log0Expected));
    $log1Expected = version_compare(\Drupal::VERSION, '10.3', '>=')
      ? [
        'uid' => 2,
        'type' => 'my_channel2',
        'message' => 'My error',
        'severity' => 3,
        'link' => '',
        'location' => TestHelpers::REQUEST_STUB_DEFAULT_URI,
        'referer' => '',
        'hostname' => '127.0.0.1',
        '_context' => [
          'uid' => 2,
          'channel' => 'my_channel2',
          'link' => '',
          'request_uri' => TestHelpers::REQUEST_STUB_DEFAULT_URI,
          'referer' => '',
          'ip' => '127.0.0.1',
        ],
      ]
      : [
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
      ];
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($logs[1], $log1Expected));
    $this->assertIsNumeric($logs[0]["timestamp"]);
    $this->assertIsNumeric($logs[1]["_context"]["timestamp"]);
    $this->assertGreaterThan($logs[0]["_microtime"], $logs[1]["_microtime"]);
  }

}
