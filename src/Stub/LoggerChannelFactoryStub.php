<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\test_helpers\lib\StaticLogger;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default LoggerChannelFactory class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class LoggerChannelFactoryStub extends LoggerChannelFactory {

  /**
   * A static logger instance.
   *
   * @var \Drupal\test_helpers\lib\StaticLogger
   */
  protected $staticLogger;

  /**
   * Constructs a new LoggerChannelFactory class.
   */
  public function __construct() {
    TestHelpers::service('request_stack');
    TestHelpers::service('current_user');

    $this->staticLogger = new StaticLogger();
    $this->addLogger($this->staticLogger);
  }

  /**
   * Returns the array with all collected logs.
   *
   * @return array
   *   The array of logs.
   */
  public function stubGetLogs() {
    return $this->staticLogger->getLogs();
  }

}
