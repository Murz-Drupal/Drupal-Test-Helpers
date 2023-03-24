<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\language\Config\LanguageConfigFactoryOverride;
use Drupal\language\Config\LanguageConfigFactoryOverrideInterface;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A stub of the Drupal's default ConfigurableLanguageManager class.
 */
class ConfigurableLanguageManagerStub extends ConfigurableLanguageManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    LanguageDefault $default_language = NULL,
    ConfigFactoryInterface $config_factory = NULL,
    ModuleHandlerInterface $module_handler = NULL,
    LanguageConfigFactoryOverrideInterface $config_override = NULL,
    RequestStack $request_stack = NULL
  ) {
    $default_language_values = [
      'id' => 'en',
      // In a configuration record the 'label' term is used instead of 'name'.
      'label' => LanguageManager::getStandardLanguageList()['en'][0],
    ];
    $default_language ??= new LanguageDefault($default_language_values);
    $config_factory ??= TestHelpers::service('config.factory');
    $module_handler ??= TestHelpers::service('module_handler');
    $config_override ??= TestHelpers::service('language.config_factory_override', LanguageConfigFactoryOverride::class);
    $request_stack ??= TestHelpers::service('request_stack');
    $config_factory->stubSetConfig('language.entity.' . $default_language_values['id'], $default_language_values);

    parent::__construct($default_language, $config_factory, $module_handler, $config_override, $request_stack);

  }

  /**
   * Adds a language to the stub.
   *
   * @param string $code
   *   A language code.
   * @param string|null $label
   *   A label for the language, if NULL - getted from standart list.
   */
  public function stubAddLanguage(string $code, string $label = NULL) {
    $values = $this->languageValuesFromCode($code, $label);
    // In a configuration record the 'label' term is used instead of 'name'.
    if (isset($values['label'])) {
      $values['name'] = $values['label'];
    }
    $this->configFactory->stubSetConfig('language.entity.' . $code, $values);

    // Resetting the static cache of languages.
    $this->languages = [];
  }

  /**
   * Adds languages to the stub.
   *
   * @param array $languages
   *   A list of languages codes.
   */
  public function stubAddLanguages(array $languages) {
    foreach ($languages as $language) {
      // @todo Add support for arrays with language code and name.
      $this->stubAddLanguage($language);
    }
  }

  /**
   * Sets current language.
   *
   * @param string|\Drupal\Core\Language\LanguageInterface $language
   *   A language code or a LanguageInterface object to set.
   */
  public function stubSetCurrentLanguage($language) {
    if (is_string($language)) {
      $language = new Language($this->languageValuesFromCode($language));
    }
    $this->stubDefaultLanguage = $language;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentLanguage($type = LanguageInterface::TYPE_INTERFACE) {
    if (isset($this->stubDefaultLanguage)) {
      return $this->stubDefaultLanguage;
    }
    else {
      return parent::getCurrentLanguage($type);
    }
  }

  /**
   * Generates value from langcode.
   *
   * @param string $langcode
   *   A langcode to use.
   * @param string|null $label
   *   A label to use, if NULL - gets from the standard list or use langcode in
   *   brackets, if no matches.
   *
   * @return array
   *   An array with values for creating a Language object.
   */
  private function languageValuesFromCode(string $langcode, string $label = NULL): array {
    $languageData = LanguageManager::getStandardLanguageList()[$langcode] ?? NULL;
    return [
      'id' => $langcode,
      'label' => $label ?? $languageData[0] ?? "[$langcode]",
      'direction' => $languageData[2] ?? LanguageInterface::DIRECTION_RTL,
    ];

  }

}
