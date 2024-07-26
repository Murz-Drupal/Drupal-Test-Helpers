<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\test_helpers\lib\StaticLogger;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\RequestStack;

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
  public function __construct(
    ?RequestStack $requestStack = NULL,
    ?AccountInterface $currentUser = NULL
  ) {
    $requestStack ??= TestHelpers::service('request_stack');
    $currentUser ??= TestHelpers::service('current_user');
    $this->staticLogger = new StaticLogger();
    $this->addLogger($this->staticLogger);
    parent::__construct($requestStack, $currentUser);
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
