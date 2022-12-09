<?php

namespace Drupal\Tests\test_helpers\Unit\Stubs;

use Drupal\Core\Language\LanguageDefault;
use Drupal\test_helpers\Stub\LanguageManagerStub;
use Drupal\Tests\UnitTestCase;

/**
 * Tests LanguageManagerStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\LanguageManagerStub
 * @group test_helpers
 */
class LanguageManagerStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testSelect() {
    $langEn = new LanguageDefault(['id' => 'en', 'name' => 'English']);
    $langRu = new LanguageDefault(['id' => 'ru', 'name' => 'Russian']);

    $stub = new LanguageManagerStub();
    $this->assertEquals($langEn->get(), $stub->getCurrentLanguage());

    $stub = new LanguageManagerStub($langRu);
    $this->assertEquals($langRu->get(), $stub->getCurrentLanguage());
    $this->assertEquals($langRu->get()->getName(), $stub->getLanguageName('ru'));
  }

}
