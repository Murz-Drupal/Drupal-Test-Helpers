<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests getPluginDefinition API function.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class GetPluginDefinitionTest extends UnitTestCase {

  /**
   * @covers ::getPluginDefinition
   */
  public function testGetPluginDefinition() {
    $definition = TestHelpers::getPluginDefinition(ConfigurableLanguage::class, 'Entity');
    $this->assertEquals('configurable_language', $definition->id());
    $this->assertEquals('language', $definition->getProvider());
    $this->assertEquals(ConfigurableLanguage::class, $definition->getClass());
  }

}
