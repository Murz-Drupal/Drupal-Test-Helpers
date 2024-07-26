<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Session\UserSession;
use Drupal\test_helpers\Stub\LoggerChannelFactoryStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\Search\UserSearch;

/**
 * Tests ConfigFactoryStub class.
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

    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($logs[0], [
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
    ]));
    $this->assertTrue(TestHelpers::isNestedArraySubsetOf($logs[1], [
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
    ]));
    $this->assertIsNumeric($logs[0]["timestamp"]);
    $this->assertIsNumeric($logs[1]["_context"]["timestamp"]);
    $this->assertGreaterThan($logs[0]["_microtime"], $logs[1]["_microtime"]);
  }

}
