<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\CoreService;

use Drupal\Core\Pager\PagerManager;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests 'pager.manager' core service.
 *
 * @covers PagerManager::findPage
 */
#[CoversClass(PagerManager::class)]
#[Group('test_helpers')]
class PagerManagerTest extends UnitTestCase {

  /**
   * Tests the findPage() method.
   */
  public function testInvalidateTags() {
    $page = 42;
    $request = Request::create("/my-page?page=$page");
    TestHelpers::service('request_stack')->push($request);
    $service = TestHelpers::service('pager.manager');
    $this->assertEquals($page, $service->findPage());
  }

}
