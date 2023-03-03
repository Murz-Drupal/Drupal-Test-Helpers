<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageDefault;
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
    $default_language ??= new LanguageDefault([
      'id' => 'en',
      'name' => LanguageManager::getStandardLanguageList()['en'][0],
    ]);
    $config_factory ??= TestHelpers::service('config.factory');
    $module_handler ??= TestHelpers::service('module_handler');
    $config_override ??= TestHelpers::service('language.config_factory_override', LanguageConfigFactoryOverride::class);
    $request_stack ??= TestHelpers::service('request_stack');

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
    if (!$label) {
      $label = LanguageManager::getStandardLanguageList()[$code][0] ?? "[$code]";
    }
    $language = [
      'id' => $code,
      'label' => $label,
    ];
    $this->configFactory->stubSetConfig('language.entity.' . $code, $language);

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

}
