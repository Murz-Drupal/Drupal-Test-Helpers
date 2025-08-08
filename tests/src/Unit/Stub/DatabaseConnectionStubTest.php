<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\Stub\DatabaseConnectionStub\Connection;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests DatabaseConnectionStub class.
 */
#[CoversClass(Connection::class)]
#[Group('test_helpers')]
#[CoversMethod(Connection::class, '__construct')]
#[CoversMethod(Connection::class, 'stubSetExecuteHandler')]
#[CoversMethod(Connection::class, 'select')]
#[CoversMethod(Connection::class, 'delete')]
#[CoversMethod(Connection::class, 'insert')]
#[CoversMethod(Connection::class, 'startTransaction')]
#[CoversMethod(Connection::class, 'popTransaction')]
#[CoversMethod(Connection::class, 'mockExecuteForMethod')]
#[CoversMethod(Connection::class, 'stubGetConnection')]
class DatabaseConnectionStubTest extends UnitTestCase {

  /**
   * A condition.
   *
   * @var \Drupal\Core\Database\Query\ConditionInterface
   */
  protected ConditionInterface $condition;

  /**
   * Tests the stubSetFormat method.
   */
  public function testStubSetFormat() {
    $database = TestHelpers::service('database');

    // Ensuring that these empty functions executes without exception.
    $database->startTransaction('tr1');

    // This throws an error on Drupal 9.x
    // ```
    // TypeError: Argument 1 passed to
    // Drupal\sqlite\Driver\Database\sqlite\Select::__construct()
    // must be an instance of Drupal\sqlite\Driver\Database\sqlite\Connection,
    // instance of Drupal\test_helpers\Stub\ConnectionStub given
    // ```
    // Skipping for this case.
    if (version_compare(\Drupal::VERSION, '10.0', '>=')) {
      $this->assertInstanceOf(
        \PDOStatement::class,
        $database->select('table1')->execute()
      );
      $this->assertEquals([], $database->select('table1')->execute()->fetchAll());
    }

    $this->assertEquals(Connection::STUB_RESULT_INSERTS, $database->insert('table1')->execute());
    $this->assertEquals(Connection::STUB_RESULT_DELETE, $database->delete('table1')->execute());

    $database->stubSetExecuteHandler(function () {
      return ['mockedResult'];
    });
    $this->assertEquals(['mockedResult'], $database->insert('table1')->execute());
    $this->assertEquals(['mockedResult'], $database->delete('table2')->execute());
    $this->assertEquals(['mockedResult'], $database->select('table3')->execute());

    $database->stubSetExecuteHandler(function () {
      return ['selectResult'];
    }, 'select');
    $database->stubSetExecuteHandler(function () {
      return ['insertResult'];
    }, 'insert');

    $this->assertEquals(['insertResult'], $database->insert('table1')->execute());
    $this->assertEquals(['selectResult'], $database->select('table3')->execute());
    $this->assertEquals(['mockedResult'], $database->delete('table2')->execute());

    $database->stubSetExecuteHandler(function () {
      return ['deleteResult'];
    }, 'delete');

    $this->assertEquals(['deleteResult'], $database->delete('table2')->execute());
  }

  /**
   * Tests Select function.
   */
  public function testSelect() {
    TestHelpers::service('database');
    $database = Database::getConnection();
    $database->stubSetExecuteHandler(function () {
      return 'resultAll';
    });
    $select = $database->select('my_table', 't');
    $select->condition('name', 'foo');
    $database->stubSetExecuteHandler(function () {
      return 'resultSelect';
    }, 'select');
    $database->stubSetExecuteHandler(function () {
      return $this->condition->conditions()[0]['value'];
    }, 'select');

    $result = $select->execute();

    $this->assertSame('foo', $result);
  }

  /**
   * Tests the static wrapper for Database::getConnection().
   */
  public function testStubGetConnection() {
    $connectionClassName = Connection::class;
    $connectionClassNamespace = substr($connectionClassName, 0, strrpos($connectionClassName, '\\'));
    $connectionInfo = [
      'driver' => 'test_helpers',
      'namespace' => $connectionClassNamespace,
    ];

    $connection1 = Database::getConnection();
    $this->checkConnection($connection1, 'default', 'default');
    $connection2 = Database::getConnection('default');
    $this->checkConnection($connection2, 'default', 'default');

    $target = 'default';
    $key = 'my_key1';
    TestHelpers::assertException(function () use ($target, $key) {
      Database::getConnection($target, $key);
    }, ConnectionNotDefinedException::class);
    Database::addConnectionInfo($key, $target, $connectionInfo);
    $connection = Database::getConnection($target, $key);
    $this->checkConnection($connection, $target, $key);

    $target = 'my_target1';
    $key = 'my_key1';
    // This should not throw an exception, but returns the default target with
    // the specific key.
    $connection1 = Database::getConnection($target, $key);
    $this->checkConnection($connection1, 'default', $key);

    // This case should throw an exception, because the key is not existing.
    TestHelpers::assertException(function () use ($target) {
      Database::getConnection($target, 'my_key2');
    }, ConnectionNotDefinedException::class);

    TestHelpers::service('database')->stubAddConnection($key, $target);
    $connection = Database::getConnection($target, $key);
    $this->checkConnection($connection, $target, $key);
  }

  /**
   * Asserts that the connection matches the expectations.
   *
   * @param mixed $connection
   *   A Connection instance.
   * @param mixed $target
   *   The target value.
   * @param mixed $key
   *   The key value.
   */
  private function checkConnection($connection, $target, $key = NULL) {
    $this->assertInstanceOf(Connection::class, $connection);
    $this->assertEquals($target, $connection->getTarget());
    if ($key) {
      $this->assertEquals($key, $connection->getKey());
    }

  }

}
