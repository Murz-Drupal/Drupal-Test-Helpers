<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\test_helpers\Stub\LanguageDefaultStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests LanguageDefaultStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\ConfigurableLanguageManagerStub
 * @group test_helpers
 */
class LanguageDefaultStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::stubAddLanguage
   */
  public function testStub() {
    /** @var \Drupal\test_helpers\Stub\LanguageDefaultStub */
    $stub = TestHelpers::service('language.default');
    $languageManager = TestHelpers::service('language_manager');

    $this->assertInstanceOf(LanguageDefaultStub::class, $stub);
    $this->assertEquals('en', $stub->get()->getId());
    $this->assertEquals('en', $languageManager->getCurrentLanguage()->getId());

    $stub->stubSetByCode('fr');
    $this->assertEquals('fr', $stub->get()->getId());
    $this->assertEquals('fr', $languageManager->getCurrentLanguage()->getId());

    $stub->set(new Language([
      'id' => 'de',
      // In a configuration record the 'label' term is used instead of 'name'.
      'label' => LanguageManager::getStandardLanguageList()['de'][0],
    ]));
    $this->assertEquals('de', $stub->get()->getId());
    $this->assertEquals('de', $languageManager->getCurrentLanguage()->getId());
  }

}
