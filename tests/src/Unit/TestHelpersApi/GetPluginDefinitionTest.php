<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests getPluginDefinition API function.
 *
 * @covers TestHelpers::getPluginDefinition
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
class GetPluginDefinitionTest extends UnitTestCase {

  /**
   * Tests the getPluginDefinition function.
   */
  public function testGetPluginDefinition() {
    $definition = TestHelpers::getPluginDefinition(ConfigurableLanguage::class, 'Entity');
    $this->assertEquals('configurable_language', $definition->id());
    $this->assertEquals('language', $definition->getProvider());
    $this->assertEquals(ConfigurableLanguage::class, $definition->getClass());
  }

}
