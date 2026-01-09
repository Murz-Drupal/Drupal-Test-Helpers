<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\test_helpers\Stub\ConfigurableLanguageManagerStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\Stub\LanguageDefaultStub;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests LanguageDefaultStub class.
 *
 * @covers ConfigurableLanguageManagerStub::__construct
 * @covers ConfigurableLanguageManagerStub::stubAddLanguage
 * @covers ConfigurableLanguageManagerStub::getCurrentLanguage
 * @covers LanguageDefaultStub::set
 * @covers LanguageDefaultStub::stubSetByCode
 */
#[CoversClass(ConfigurableLanguageManagerStub::class)]
#[CoversClass(LanguageDefaultStub::class)]
#[Group('test_helpers')]
class LanguageDefaultStubTest extends UnitTestCase {

  /**
   * Tests the LanguageDefaultStub methods.
   */
  public function testStub() {
    /** @var \Drupal\test_helpers\Stub\LanguageDefaultStub $stub */
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
