<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManager;

/**
 * A stub of the Drupal's default LanguageManager class.
 */
class LanguageManagerStub extends LanguageManager {

  public function __construct() {
    $languageDefault = new LanguageDefault(['id' => 'en', 'name' => 'English']);
    $this->defaultLanguage = $languageDefault;
  }

}
