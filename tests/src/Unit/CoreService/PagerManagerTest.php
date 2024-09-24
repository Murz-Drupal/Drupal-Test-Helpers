<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\CoreService;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests 'pager.manager' core service.
 *
 * @coversDefaultClass \Drupal\Core\Pager\PagerManager
 * @group test_helpers
 */
class PagerManagerTest extends UnitTestCase {

  /**
   * @covers ::findPage
   */
  public function testInvalidateTags() {
    $page = 42;
    $request = Request::create("/my-page?page=$page");
    TestHelpers::service('request_stack')->push($request);
    $service = TestHelpers::service('pager.manager');
    $this->assertEquals($page, $service->findPage());
  }

}
