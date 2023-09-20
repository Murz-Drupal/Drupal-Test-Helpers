<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\ModuleHandlerStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\test_helpers\Stub\ModuleHandlerStub
 * @group test_helpers
 */
class ModuleHandlerStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testStub() {
    $stub = TestHelpers::service('module_handler');
    $this->assertTrue($stub instanceof ModuleHandlerStub);
    $this->assertEquals(
      TestHelpers::getDrupalRoot(),
      TestHelpers::getPrivateProperty($stub, 'root')
    );
    $this->assertEquals(
      [],
      $stub->getModuleList()
    );
    $stub->addModule('node', 'core/modules/node');
    $stub->addModule('comment', 'core/modules/comment');
    $modulesList = $stub->getModuleList();
    $this->assertCount(2, $modulesList);
    $moduleNode = $modulesList['node'];
    $this->assertEquals(
      'core/modules/node',
      $moduleNode->getPath()
    );
  }

}
