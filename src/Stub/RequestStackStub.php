<?php

namespace Drupal\test_helpers\Stub;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A stub for the request_stack service.
 */
class RequestStackStub extends RequestStack {

  /**
   * A flag to indicate that the request stub is still pushed by default.
   */
  protected bool $isStubPushed;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->isStubPushed = TRUE;
    // Pushing an example request by default.
    $requestStub = Request::create('https://example.com/some-path');
    $this->push($requestStub);
  }

  /**
   * {@inheritdoc}
   */
  public function push(Request $request) {
    if ($this->isStubPushed) {
      $this->pop();
      $this->isStubPushed = FALSE;
    }
    parent::push($request);
  }

}
