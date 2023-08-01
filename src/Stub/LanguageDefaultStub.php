<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default ConfigurableLanguageManager class.
 */
class LanguageDefaultStub extends LanguageDefault {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values = NULL) {
    if ($values === NULL) {
      $values = [
        'id' => 'en',
        // In a configuration record the 'label' term is used instead of 'name'.
        'label' => LanguageManager::getStandardLanguageList()['en'][0],
      ];
    }
    // This service is required to reset kernel container when the default
    // language is changed.
    TestHelpers::service('kernel');

    TestHelpers::service('config.factory')->stubSetConfig('language.entity.' . $values['id'], $values);

    parent::__construct($values);
  }

  /**
   * {@inheritdoc}
   */
  public function set(LanguageInterface $language) {
    parent::set($language);
    if (\Drupal::hasService('language_manager')) {
      \Drupal::service('language_manager')->reset();
    }
  }

  /**
   * Sets the new default language by the language code.
   */
  public function stubSetByCode($code) {
    $language = new Language([
      'id' => $code,
      // In a configuration record the 'label' term is used instead of 'name'.
      'label' => LanguageManager::getStandardLanguageList()[$code][0],
    ]);
    $this->set($language);
    TestHelpers::service('language_manager')->reset();
  }

}
