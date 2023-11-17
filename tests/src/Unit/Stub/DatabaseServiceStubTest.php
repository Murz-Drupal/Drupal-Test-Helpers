<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\test_helpers\Stub\DatabaseStub;
use Drupal\Tests\UnitTestCase;

/**
 * Tests DatabaseStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\DatabaseStub
 * @group test_helpers
 */
class DatabaseServiceStubTest extends UnitTestCase {

  /**
   * A condition.
   *
   * @var \Drupal\Core\Database\Query\ConditionInterface
   */
  protected ConditionInterface $condition;

  /**
   * Tests Select function.
   *
   * @covers ::__construct
   * @covers ::select
   */
  public function testSelect() {
    $database = new DatabaseStub();
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
