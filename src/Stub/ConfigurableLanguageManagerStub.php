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
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A stub of the Drupal's default ConfigurableLanguageManager class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class ConfigurableLanguageManagerStub extends ConfigurableLanguageManager {

  /**
   * A default stub language.
   *
   * @var string
   */
  protected Language $stubDefaultLanguage;

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
    $default_language ??= TestHelpers::service('language.default');
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
    $values = $this->languageValuesFromCode($code, $label);
    // In a configuration record the 'label' term is used instead of 'name'.
    if (isset($values['label'])) {
      $values['name'] = $values['label'];
    }
    TestHelpers::saveEntity(ConfigurableLanguage::class, $values);
    $this->stubClearStaticCaches();
  }

  /**
   * Adds languages to the stub.
   *
   * @param array $languagecodes
   *   A list of languages codes.
   */
  public function stubAddLanguages(array $languagecodes) {
    foreach ($languagecodes as $languagecode) {
      // @todo Add support for arrays with language code and name.
      $this->stubAddLanguage($languagecode);
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
  public function languageValuesFromCode(string $langcode, string $label = NULL): array {
    if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $languageData = ['Not specified', 'Not specified'];
    }
    else {
      $languageData = LanguageManager::getStandardLanguageList()[$langcode] ?? NULL;
    }
    return [
      'id' => $langcode,
      'label' => $label ?? $languageData[0] ?? "[$langcode]",
      'direction' => $languageData[2] ?? LanguageInterface::DIRECTION_RTL,
    ];

  }

  /**
   * Clears all static caches for the service.
   */
  private function stubClearStaticCaches() {
    $this->initialized = FALSE;
    $this->negotiatedLanguages = [];
    $this->negotiatedMethods = [];
    $this->languageTypes = NULL;
    $this->languageTypesInfo = NULL;
    $this->languages = [];
  }

}
