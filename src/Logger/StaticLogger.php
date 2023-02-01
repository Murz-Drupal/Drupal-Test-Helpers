<?php

namespace Drupal\test_helpers\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Redirects logging messages to syslog.
 */
class StaticLogger implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * A storage for logs.
   *
   * @var array
   */
  public array $logs;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    $this->logs[] = [
      'uid' => $context['uid'] ?? NULL,
      'type' => $context['channel'] ?? NULL,
      'message' => $message,
      'severity' => $level,
      'link' => $context['link'] ?? NULL,
      'location' => $context['request_uri'] ?? NULL,
      'referer' => $context['referer'] ?? NULL,
      'hostname' => $context['ip'] ?? NULL,
      'timestamp' => $context['timestamp'] ?? NULL,
      '_context' => $context,
      '_microtime' => microtime(TRUE),
    ];
  }

  /**
   * Returns the logs array.
   *
   * @return array
   *   The logs array.
   */
  public function getLogs() {
    return $this->logs;
  }

}
