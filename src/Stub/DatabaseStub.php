<?php

namespace Drupal\test_helpers\Stub;

use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\test_helpers\UnitTestHelpers;
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

  /**
   * Mocks the execute function for a method.
   *
   * @param string $method
   *   The method name.
   * @param array $methodArguments
   *   The list of arguments of the method.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked method.
   */
  private function mockExecuteForMethod(string $method, array $methodArguments) {
    $originalMethod = parent::$method(...$methodArguments);
    $class = \get_class($originalMethod);
    $mockedMethod = UnitTestHelpers::createPartialMockWithConstructor($class, [
      'execute',
    ],
    [$this, ...$methodArguments],
    [
      'stubExecute',
    ]);

    $stubExecuteHandlers = &$this->stubExecuteHandlers;
    $executeFunction = function () use (&$stubExecuteHandlers, $method) {
      $function =
        $stubExecuteHandlers[$method]
        ?? $stubExecuteHandlers['all']
        ?? function () {
          return [];
        };

      UnitTestHelpers::setClassMethod($this, 'stubExecute', $function);
      return $this->stubExecute();
    };
    UnitTestHelpers::setClassMethod($mockedMethod, 'execute', $executeFunction);

    return $mockedMethod;
  }

  /**
   * {@inheritDoc}
   */
  public function select($table, $alias = NULL, array $options = []) {
    $methodArguments = \func_get_args();
    $select = $this->mockExecuteForMethod('select', $methodArguments);
    return $select;
  }

  /**
   * Sets the function to handle execute calls.
   *
   * @param \Closure $executeFunction
   *   The execute function.
   * @param string $method
   *   The method to use, all methods by default.
   */
  public function stubSetExecuteHandler(\Closure $executeFunction, string $method = 'all') {
    $this->stubExecuteHandlers[$method] = $executeFunction;
  }

}
