<?php

declare(strict_types=1);

namespace Drupal\test_helpers_functional_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the TestHelpersFunctional test helper pages.
 */
class TestHelpersFunctionalTestController extends ControllerBase {

  /**
   * A page for testing command thPerformAndWaitForReRender().
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response with HTML.
   */
  public function rerenderTestPage(): Response {
    $content = file_get_contents(__DIR__ . '/../../fixtures/html-rerender-test-page.html');
    return new Response($content);
  }

}
