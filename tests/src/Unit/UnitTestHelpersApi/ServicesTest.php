<?php

namespace Drupal\Tests\test_helpers\Unit\UnitTestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Core\Site\Settings;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\LanguageNegotiationMethodManager;

/**
 * Tests Query helper functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class ServicesTest extends UnitTestCase {

  /**
   * @covers ::service
   * @covers ::setServices
   */
  public function testServices() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject $entityType */
    $entityType = $this->createMock(EntityTypeInterface::class);
    $entityType->method('getSingularLabel')->willReturn('my entity');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityTypeManager */
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getDefinition')->willReturn($entityType);

    TestHelpers::service('string_translation');
    TestHelpers::setServices([
      'url_generator' => UrlGenerator::class,
    ]);

    // Checking initialized services.
    try {
      $service = TestHelpers::createClass(EntityController::class);
      $this->fail("Expected ServiceNotFoundException is not thrown.");
    }
    catch (ServiceNotFoundException $e) {
      $this->assertEquals('You have requested a non-existent service "entity_type.manager".', $e->getMessage());
    }

    TestHelpers::setServices([
      'entity_type.bundle.info' => NULL,
      'renderer' => NULL,
      'entity_type.manager' => $entityTypeManager,
      'entity.repository' => NULL,
      'current_route_match' => NULL,
    ]);

    // Testing the behavior on a real service with the 'create' function.
    $service = TestHelpers::createClass(EntityController::class);
    $result = $service->addTitle('my_entity');
    $this->assertSame('Add my entity', $result->__toString());

    // Checking resetting of the container.
    TestHelpers::setServices(['entity.repository'], TRUE);
    try {
      $service = TestHelpers::createClass(EntityController::class);
      $this->fail('Previous line should throw an exception.');
    }
    catch (ServiceNotFoundException $e) {
      $this->assertStringStartsWith('You have requested a non-existent service', $e->getMessage());
    }

    /* Testing services initialization. */

    // Resetting the container to have a clear environment.
    TestHelpers::getContainer(TRUE);

    // With no initialization flag here should be a mock that always return
    // NULL.
    TestHelpers::service('country_manager', NULL, NULL, NULL, NULL, FALSE);
    $this->assertNull(\Drupal::service('country_manager')->getList());

    // With the initialization flag here should be a real initialized object.
    TestHelpers::service('country_manager', NULL, TRUE, NULL, NULL, TRUE);
    try {
      $this->assertIsArray(\Drupal::service('country_manager')->getList());
      $this->fail();
    }
    catch (\Exception $e) {
    }
    TestHelpers::service('string_translation');
    $this->assertIsArray(\Drupal::service('country_manager')->getList());
    $this->assertEquals('foo', \Drupal::service('string_translation')->translate('foo'));

    // With the initialization flag equals FALSE the auto initialized services
    // should return NULL always.
    TestHelpers::service('string_translation', NULL, TRUE, NULL, NULL, FALSE);
    $this->assertNull(\Drupal::service('string_translation')->translate('foo'));
  }

  /**
   * @covers ::initServiceFromYaml
   */
  public function testInitServiceFromYaml() {
    TestHelpers::service('plugin.manager.language_negotiation_method', $this->createMock(LanguageNegotiationMethodManager::class));
    \Drupal::service('plugin.manager.language_negotiation_method')
      ->method('getDefinitions')
      ->willReturn(['method1', 'method2']);
    TestHelpers::setServices([
      'config.factory' => NULL,
      'language_manager' => $this->createMock(ConfigurableLanguageManagerInterface::class),
      'settings' => new Settings([]),
      'request_stack' => NULL,
    ]);
    $service = TestHelpers::initServiceFromYaml(
      'core/modules/language/language.services.yml',
      'language_negotiator');
    $this->assertEquals(['method1', 'method2'], $service->getNegotiationMethods());
  }

  /**
   * @covers ::initService
   */
  public function testInitService() {
    $service = TestHelpers::initService(LanguageNegotiationMethodManager::class);
    $this->assertInstanceOf(LanguageNegotiationMethodManager::class, $service);

    $service = TestHelpers::initService(LanguageNegotiationMethodManager::class, 'plugin.manager.language_negotiation_method');
    $this->assertInstanceOf(LanguageNegotiationMethodManager::class, $service);

    try {
      $service = TestHelpers::initService(LanguageNegotiationMethodManager::class, 'wrong_service_name');
      $this->fail('Previous line should throw an exception.');
    }
    catch (\Exception $e) {
      $this->assertStringStartsWith("The service name 'plugin.manager.language_negotiation_method' differs from required name 'wrong_service_name'", $e->getMessage());
    }

    // The case with the service name as an argument is tested in the
    // Drupal\Tests\test_helpers_example\Unit\ConfigEventsSubscriberTest()
    // because it requires a 'services.yml' file to be presend, but for this
    // module it is not needed.
  }

}
