<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Database\Transaction;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * A stub of the Drupal's default Connection class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
/**
 * Class ConnectionStub extends Connection {.
 */
class ConnectionStub extends StubConnection {

  const STUB_RESULT_INSERTS = 1;
  const STUB_RESULT_DELETE = 1;

  /**
   * The static storage for execute functions.
   *
   * @var array
   */
  protected array $stubExecuteHandlers = [];
  /**
   * The static storage for execute functions.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected MockObject $pdoMock;

  /**
   * Constructs a new object.
   */
  public function __construct() {
    $this->pdoMock = TestHelpers::createMock(StubPDO::class);
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
    $mockedMethod = TestHelpers::createPartialMockWithConstructor(
      $class,
      [
        'execute',
      ],
      [
        $this,
        ...$methodArguments,
      ],
      [
        'stubExecute',
      ]
    );

    $stubExecuteHandlers = &$this->stubExecuteHandlers;
    $executeFunction = function () use (&$stubExecuteHandlers, $method) {
      $function =
        $stubExecuteHandlers[$method]
        ?? $stubExecuteHandlers['all']
        ?? function () use ($method) {
          switch ($method) {
            case 'select':
              $pdoStatement = TestHelpers::createMock(\PDOStatement::class);
              return $pdoStatement;

            case 'merge':
            case 'upsert':
            case 'insert':
              return ConnectionStub::STUB_RESULT_INSERTS;

            case 'delete':
              return ConnectionStub::STUB_RESULT_DELETE;

            default:
              return NULL;
          }
        };

      TestHelpers::setMockedClassMethod($this, 'stubExecute', $function);
      // @phpstan-ignore-next-line
      return $this->stubExecute();
    };
    TestHelpers::setMockedClassMethod($mockedMethod, 'execute', $executeFunction);

    return $mockedMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function select($table, $alias = NULL, array $options = []) {
    $methodArguments = \func_get_args();
    $select = $this->mockExecuteForMethod('select', $methodArguments);
    return $select;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($table, array $options = []) {
    $methodArguments = \func_get_args();
    $delete = $this->mockExecuteForMethod('delete', $methodArguments);
    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function insert($table, array $options = []) {
    $methodArguments = \func_get_args();
    $insert = $this->mockExecuteForMethod('insert', $methodArguments);
    return $insert;
  }

  /**
   * Sets the function to handle execute calls.
   *
   * You can call `$this->stubExecuteBase()` in your custom callback function
   * to execute the base stub behavior for the query.
   *
   * @param \Closure $executeFunction
   *   The execute function.
   * @param string $method
   *   The exact method to set (insert, select, delete), all methods by default.
   */
  public function stubSetExecuteHandler(\Closure $executeFunction, string $method = 'all') {
    $this->stubExecuteHandlers[$method] = $executeFunction;
  }

  /**
   * A stub of original function to do nothing.
   *
   * {@inheritdoc}
   */
  public function startTransaction($name = '') {
    return TestHelpers::createMock(Transaction::class);
  }

  /**
   * A stub of original function to do nothing.
   *
   * {@inheritdoc}
   */
  public function popTransaction($name) {
  }

}
