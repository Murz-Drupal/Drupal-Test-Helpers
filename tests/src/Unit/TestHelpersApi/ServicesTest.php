<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\Plugin\Derivative\DynamicLocalTasks;
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

  use StringTranslationTrait;

  /**
   * @covers ::service
   * @covers ::setServices
   * @covers ::createClass
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
   * @covers ::service
   */
  public function testServiceMocked() {
    $testClass = $this;

    TestHelpers::service('string_translation');

    $renderer = TestHelpers::service('renderer');

    // The classical approach.
    $renderer->method('render')->willReturnCallback(function (array &$element) {
      // We can't get access to `$this` keyword here, because PHPUnit approach
      // executes the callback function in the unit test context, not in the
      // service's class (Renderer).
      /* $this->replacePlaceholders($element); */

      return [
        '#markup' => $this->t('Element @title', [
          '@title' => $element['#title'],
        ]),
      ];
    });

    // The modern approaches.
    // Using the setPrivateProperty() we can set values for any private or
    // protected property.
    TestHelpers::setPrivateProperty($renderer, 'theme', 'My theme');

    TestHelpers::setMockedClassMethod($renderer, 'renderRoot',
      function (array &$element) use ($testClass) {
        // Using the setMockedClassMethod() we can get access to `$this` keyword
        // with any private and protected properties and methods.
        /** @var \Drupal\Core\Render\Renderer $this */
        $this->replacePlaceholders($element);
        return [
          '#markup' => TestHelpers::callPrivateMethod($testClass, 't', [
            'Root for @title with @theme', [
              '@title' => $element['#title'],
              '@theme' => $this->theme,
            ],
          ]),
        ];
      });

    $element = ['#title' => 'My Element'];
    $this->assertEquals('Element My Element', (string) \Drupal::service('renderer')->render($element)['#markup']);
    $this->assertEquals('Root for My Element with My theme', (string) \Drupal::service('renderer')->renderRoot($element)['#markup']);
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

  /**
   * @covers ::createClass
   */
  public function testCreateClass() {
    TestHelpers::setServices([
      'string_translation',
      'entity_type.manager',
    ]);
    $class = TestHelpers::createClass(
      DynamicLocalTasks::class,
      ['plugin_id',
      ],
      ['config.factory']);

    $this->assertInstanceOf(DynamicLocalTasks::class, $class);
  }

  /**
   * @covers ::initEntityTypeManagerStubs
   */
  public function testInitEntityTypeManagerStubs() {
    TestHelpers::initEntityTypeManagerStubs();
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $this->assertInstanceOf(EntityTypeManagerInterface::class, $entityTypeManager);
  }

}
