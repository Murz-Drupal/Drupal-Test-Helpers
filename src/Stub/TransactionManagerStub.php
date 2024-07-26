<?php

declare(strict_types=1);

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Database\Transaction\TransactionManagerBase;

/**
 * SQLite implementation of TransactionManagerInterface.
 */
class TransactionManagerStub extends TransactionManagerBase {

  /**
   * {@inheritdoc}
   */
  protected function beginClientTransaction(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientTransaction(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function commitClientTransaction(): bool {
    return TRUE;
  }

}
