<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\PermissionHandlerStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests PermissionHandlerStub class.
 *
 * @covers PermissionHandlerStub::stubSetPermissions
 * @covers PermissionHandlerStub::stubAddPermissions
 * @covers PermissionHandlerStub::stubDeletePermissions
 */
#[CoversClass(PermissionHandlerStub::class)]
#[Group('test_helpers')]
class PermissionHandlerStubTest extends UnitTestCase {

  /**
   * Tests the PermissionHandlerStub API.
   */
  public function testStub() {
    $service = TestHelpers::service('user.permissions');
    $this->assertEquals([], $service->getPermissions());

    $permissionsBundle1 = [
      'create any block content' => [
        'title' => 'T1',
        'description' => 'D1',
        'dependencies' => [],
        'provider' => 'p1',
      ],
      'delete any block content' => [
        'title' => 'T2',
        'description' => 'D2',
      ],
    ];
    $permissionsBundle2 = [
      'delete any block content' => [
        'title' => 'T2v2',
        'description' => 'D2v2',
      ],
      'view restricted block content' => [
        'title' => 'T3',
      ],
    ];

    $service->stubAddPermissions($permissionsBundle1);
    $this->assertEquals($permissionsBundle1, $service->getPermissions());
    $service->stubAddPermissions($permissionsBundle2);
    $this->assertEquals($permissionsBundle1 + $permissionsBundle2, $service->getPermissions());

    $service->stubSetPermissions($permissionsBundle2);
    $this->assertEquals($permissionsBundle2, $service->getPermissions());

    $service->stubDeletePermissions(['delete any block content']);
    unset($permissionsBundle2['delete any block content']);
    $this->assertEquals($permissionsBundle2, $service->getPermissions());
  }

}
