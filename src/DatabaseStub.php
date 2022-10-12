<?php

namespace Drupal\test_helpers;

use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * A stub of the Drupal's default Connection class.
 */
class DatabaseStub extends Connection {
  /**
   * The UnitsTestHelpers.
   *
   * @var Drupal\test_helpers\UnitTestHelpers
   */
  protected $unitTestHelpers;

  /**
   * The static storage for execute functions.
   *
   * @var array
   */
  protected $stubExecuteHandlers;

  /**
   * Constructs a new object.
   */
  public function __construct() {
    $this->pdoMock = UnitTestHelpers::createMock(StubPDO::class);
    $this->connectionOptions = [
      'namespace' => 'Drupal\sqlite\Driver\Database\sqlite',
    ];
    parent::__construct($this->pdoMock, $this->connectionOptions);
  }

  private function mockExecuteForMethod($method, $arguments, $executeFunction) {
    $originalMethod = parent::$method(...$arguments);
    $class = \get_class($originalMethod);
    $mockedMethod = UnitTestHelpers::createPartialMockWithConstructor($class, [
      'execute',
    ], [$this, ...$arguments]);

    $executeFunction = $this->stubExecuteHandlers[$method]
      ?? $this->stubExecuteHandlers['all']
      ?? function () {
        return 'default';
      };
    UnitTestHelpers::bindClosureToClassMethod($executeFunction, $mockedMethod, 'execute');
    return $mockedMethod;
  }

  public function select($table, $alias = NULL, array $options = []) {
    $arguments = \func_get_args();
    $executeFunction = function () {
      return 123;
    };
    $select = $this->mockExecuteForMethod('select', $arguments, $executeFunction);
    return $select;
  }

  public function stubAddExecuteHandler(\Closure $executeFunction, string $method = 'all') {
    $this->stubExecuteHandlers[$method] = $executeFunction;
  }

}
