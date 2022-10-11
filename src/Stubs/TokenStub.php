<?php

namespace Drupal\test_helpers\Stubs;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * The TypedDataManagerStubFactory class.
 */
class TokenStub extends Token {

  /**
   * Constructs a new TypedDataManagerStubFactory.
   */
  public function __construct() {
    $this->renderer = UnitTestHelpers::createMock(RendererInterface::class);
  }

}
