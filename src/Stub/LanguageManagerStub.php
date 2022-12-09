<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManager;

/**
 * A stub of the Drupal's default LanguageManager class.
 */
class LanguageManagerStub extends LanguageManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageDefault $default_language = NULL) {
    if (!$default_language) {
      $default_language = new LanguageDefault(['id' => 'en', 'name' => 'English']);
    }
    $this->defaultLanguage = $default_language;
  }

}
