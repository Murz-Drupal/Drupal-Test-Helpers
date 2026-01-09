<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\Stub\ModuleHandlerStub;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests ModuleHandlerStub class.
 *
 * @covers ModuleHandlerStub::__construct
 */
#[CoversClass(ModuleHandlerStub::class)]
#[Group('test_helpers')]
class ModuleHandlerStubTest extends UnitTestCase {

  /**
   * Tests the ModuleHandlerStub API.
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
    $stub->stubAddModule('node', 'core/modules/node');
    $stub->stubAddModule('comment', 'core/modules/comment');
    $stub->stubAddProfile('minimal', 'core/profiles/minimal');
    $modulesList = $stub->getModuleList();
    $this->assertCount(3, $modulesList);
    $moduleCheck = $modulesList['node'];
    $this->assertEquals(
      'core/modules/node',
      $moduleCheck->getPath()
    );
    $moduleCheck = $modulesList['minimal'];
    $this->assertEquals(
      'core/profiles/minimal',
      $moduleCheck->getPath()
    );
  }

}
