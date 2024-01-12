<?php

namespace Drupal\test_helpers\Stub;

use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A stub for the request_stack service.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class RequestStackStub extends RequestStack {

  /**
   * A flag to indicate that the request stub is still pushed by default.
   */
  protected bool $isStubPushed = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Pushing an example request by default.
    $requestStub = Request::create(TestHelpers::REQUEST_STUB_DEFAULT_URI);
    $this->push($requestStub);
    $this->isStubPushed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function push(Request $request): void {
    if ($this->isStubPushed) {
      $this->pop();
      $this->isStubPushed = FALSE;
    }
    parent::push($request);
  }

}
