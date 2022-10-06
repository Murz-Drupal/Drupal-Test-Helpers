<?php

namespace Drupal\test_helpers;

use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * The ConnectionStubFactory class.
 *
 * A stub for class Drupal\Driver\Database\fake\Connection.
 */
class DatabaseStubFactory {
  /**
   * The UnitsTestHelpers.
   *
   * @var Drupal\test_helpers\UnitTestHelpers
   */
  protected $unitTestHelpers;

  /**
   * Constructs a new object.
   */
  public function __construct() {
    $this->unitTestHelpers = UnitTestHelpers::getInstance();
    $this->pdoMock = $this->unitTestHelpers->createMock(StubPDO::class);
    $this->connectionSettings = [];
  }

  public function get() {
    /**
     * @todo Replace Drupal\sqlite\Driver\Database\sqlite\Connection to
     * Drupal\Driver\Database\fake\Connection.
     * require_once DRUPAL_ROOT . '/core/tests/fixtures/database_drivers/core/corefake/Connection.php';
     * does not work because of namespaces, check the answers
     * https://stackoverflow.com/questions/57322376/cannot-load-class-with-require-once-when-using-namespace
     * for possible solutions.
     */
    $connection = $this->unitTestHelpers->createPartialMockWithCostructor(
      Connection::class,
      ['select'],
      [$this->pdoMock, $this->connectionSettings]
    );
    return $connection;
  }

  /**
   * Registers the class as the 'database' service.
   */
  public function registerService() {
    $connection = new ConnectionStub();
    UnitTestHelpers::addToContainer('database', $connection);
    return $connection;
  }

}
