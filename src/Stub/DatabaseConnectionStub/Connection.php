<?php

namespace Drupal\test_helpers\Stub\DatabaseConnectionStub;

use Drupal\Core\Database\Connection as CoreDatabaseConnection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Upsert;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\Transaction\TransactionManagerInterface;
use Drupal\test_helpers\Stub\TransactionManagerStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * A stub of the Drupal's default Connection class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 *
 *  @todo Consider extending the StubConnection instead of Connection.
 */
class Connection extends CoreDatabaseConnection {

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
   * {@inheritdoc}
   */
  public function __construct(\PDO $connection = NULL, array $connection_options = []) {
    $this->pdoMock = $connection;
    $this->connectionOptions = $connection_options;
    $this->connectionOptions['namespace'] ??= self::getNamespace(self::class);
    $this->connection = $connection;
  }

  /**
   * Gets a database connection and initiates a new stub if missing.
   *
   * @param string $target
   *   (optional) The target connection.
   * @param string|null $key
   *   (optional) The key for the connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public static function stubGetConnection($target = 'default', $key = NULL): CoreDatabaseConnection {
    $key ??= 'default';
    if (empty(Database::getConnectionInfo($key))) {
      $className = Connection::class;
      $namespace = substr($className, 0, strrpos($className, '\\'));

      Database::addConnectionInfo($key, $target, [
        'driver' => 'test_helpers',
        'namespace' => $namespace,
      ]);
    }

    return Database::getConnection($target, $key);
  }

  /**
   * Adds a new database connection with auto-generated info for the stub.
   *
   * @param string|null $key
   *   (optional) The key for the connection.
   * @param string $target
   *   (optional) The target connection.
   * @param array|null $connectionInfo
   *   (optional) The connection info.
   */
  public static function stubAddConnection(?string $key = NULL, string $target = 'default', ?array $connectionInfo = NULL): void {
    if (!$connectionInfo) {
      $connectionInfo = [
        'driver' => 'test_helpers',
        'namespace' => self::getNamespace(self::class),
      ];
    }
    Database::addConnectionInfo($key, $target, $connectionInfo);
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []) {
    $pdoMock = TestHelpers::createMock(StubPDO::class);
    return $pdoMock;
  }

  /**
   * {@inheritdoc}
   */
  public function createDatabase($database) {
  }

  /**
   * {@inheritdoc}
   */
  public function upsert($table, array $options = []) {
    return TestHelpers::createMock(Upsert::class);
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    return TestHelpers::createMock(Schema::class);
  }

  /**
   * {@inheritdoc}
   */
  public function databaseType() {
    return 'test_helpers';

  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'test_helpers';
  }

  /**
   * {@inheritdoc}
   */
  public function mapConditionOperator($operator) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function nextId($existing_id = 0) {
    return $existing_id + 1;
  }

  /**
   * {@inheritdoc}
   */
  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);

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
              return Connection::STUB_RESULT_INSERTS;

            case 'delete':
              return Connection::STUB_RESULT_DELETE;

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
  public function stubSetExecuteHandler(\Closure $executeFunction, string $method = 'all'): void {
    $this->stubExecuteHandlers[$method] = $executeFunction;
  }

  /**
   * {@inheritdoc}
   */
  protected function driverTransactionManager(): TransactionManagerInterface {
    $transactionManager = new TransactionManagerStub($this);
    return $transactionManager;
  }

  /**
   * A stub of original function to do nothing.
   *
   * {@inheritdoc}
   */
  public function popTransaction($name) {
  }

  /**
   * Returns the namespace of the class.
   *
   * @param string $fqcn
   *   The full qualified class name.
   *
   * @return string
   *   The namespace of the class.
   */
  private static function getNamespace(string $fqcn) {
    return substr($fqcn, 0, strrpos($fqcn, '\\'));
  }

  /**
   * A stub of original function to do nothing.
   *
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    return $query;
  }

}
