<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\DatabaseStub;
use Drupal\Tests\UnitTestCase;

/**
 * Tests DateFormatterStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\DatabaseStub
 * @group test_helpers
 */
class DatabaseStubTest extends UnitTestCase {

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
    $database = new DatabaseStub();

    // Ensuring that these empty functions executes without exception.
    $database->startTransaction('tr1');
    $database->popTransaction('tr1');

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

}
