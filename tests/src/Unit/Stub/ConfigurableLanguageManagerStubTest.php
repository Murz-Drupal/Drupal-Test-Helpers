<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Language\LanguageManager;
use Drupal\test_helpers\Stub\ConfigurableLanguageManagerStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigurableLanguageManagerStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\ConfigurableLanguageManagerStub
 * @group test_helpers
 */
class ConfigurableLanguageManagerStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::stubAddLanguage
   */
  public function testStub() {
    /** @var \Drupal\test_helpers\Stub\ConfigurableLanguageManagerStub */
    $configurableLanguageManagerStub = TestHelpers::service('language_manager');

    $this->assertInstanceOf(ConfigurableLanguageManagerStub::class, $configurableLanguageManagerStub);

    $languages = $configurableLanguageManagerStub->getLanguages();
    $this->assertCount(1, $languages);
    $this->assertEquals(LanguageManager::getStandardLanguageList()['en'][0], $languages['en']->getName());

    $configurableLanguageManagerStub->stubAddLanguage('fr');
    $languages = $configurableLanguageManagerStub->getLanguages();
    $this->assertCount(2, $languages);
    $this->assertEquals(LanguageManager::getStandardLanguageList()['en'][0], $languages['en']->getName());
    $this->assertEquals(LanguageManager::getStandardLanguageList()['fr'][0], $languages['fr']->getName());

    $configurableLanguageManagerStub->stubAddLanguage('de-xx', 'DE custom language');
    $languages = $configurableLanguageManagerStub->getLanguages();
    $this->assertCount(3, $languages);
    $this->assertEquals('DE custom language', $languages['de-xx']->getName());
  }

}
