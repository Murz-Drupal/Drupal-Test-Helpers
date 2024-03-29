<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A stub of the Drupal's default DateFormatter class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class DateFormatterStub extends DateFormatter {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    TranslationInterface $translation,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack
  ) {
    // Creating default fallback format.
    $entity_type_manager->stubGetOrCreateStorage(DateFormat::class);
    $this->stubSetFormat('fallback', 'Fallback date format', 'D, m/d/Y - H:i', TRUE);

    parent::__construct($entity_type_manager, $language_manager, $translation, $config_factory, $request_stack);
  }

  /**
   * Sets the date format.
   */
  public function stubSetFormat($name, $label, $pattern, $locked = 0) {
    TestHelpers::saveEntity(DateFormat::class, [
      'id' => $name,
      'label' => $label,
      'pattern' => $pattern,
      'locked' => $locked,
    ]);
  }

}
