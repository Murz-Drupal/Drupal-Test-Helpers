<?php

namespace Drupal\test_helpers\lib;

use Psr\Log\LoggerInterface;

/**
 * Redirects logging messages to syslog.
 */
class StaticLogger implements LoggerInterface {
  /**
   * A storage for logs.
   *
   * @var array
   */
  public array $logs = [];

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
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

  /* ************************************************************************ *
   * A copy-paste of Drupal\Core\Logger\RfcLoggerTrait from 9.5.x to make it
   * works with PHP 7.4 and PHP 8.0 together. It doesn't work as a dependency
   * because of missing support for Union types in PHP 7.4.
   * ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function emergency($message, array $context = []): void {
    $this->log(RfcLogLevel::EMERGENCY, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function alert($message, array $context = []): void {
    $this->log(RfcLogLevel::ALERT, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function critical($message, array $context = []): void {
    $this->log(RfcLogLevel::CRITICAL, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function error($message, array $context = []): void {
    $this->log(RfcLogLevel::ERROR, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function warning($message, array $context = []): void {
    $this->log(RfcLogLevel::WARNING, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function notice($message, array $context = []): void {
    $this->log(RfcLogLevel::NOTICE, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function info($message, array $context = []): void {
    $this->log(RfcLogLevel::INFO, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function debug($message, array $context = []): void {
    $this->log(RfcLogLevel::DEBUG, $message, $context);
  }

}
