<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\test_helpers\Stub\ConnectionStub;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConnectionStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\ConnectionStub
 * @group test_helpers
 */
class ConnectionStubTest extends UnitTestCase {

  /**
   * A condition.
   *
   * @var \Drupal\Core\Database\Query\ConditionInterface
   */
  protected ConditionInterface $condition;

  /**
   * @covers ::__construct
   * @covers ::stubSetExecuteHandler
   * @covers ::select
   * @covers ::delete
   * @covers ::insert
   * @covers ::startTransaction
   * @covers ::popTransaction
   * @covers ::mockExecuteForMethod
   */
  public function testStubSetFormat() {
    $database = new ConnectionStub();

    // Ensuring that these empty functions executes without exception.
    $database->startTransaction('tr1');

    $this->assertEquals([], $database->select('table1')->execute());
    $this->assertEquals([], $database->insert('table1')->execute());
    $this->assertEquals([], $database->delete('table1')->execute());

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
   *
   * @covers ::__construct
   * @covers ::select
   */
  public function testSelect() {
    $database = new ConnectionStub();
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

}
