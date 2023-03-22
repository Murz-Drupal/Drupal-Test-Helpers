<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Core\Site\Settings;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\LanguageNegotiationMethodManager;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Tests UnitTestHelpers functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\UnitTestHelpers
 * @group test_helpers
 */
class UnitTestHelpersTest extends UnitTestCase {

  /**
   * @covers ::getMockedMethod
   */
  public function testGetMockedMethod() {
    /** @var \Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject $mock */
    $mock = $this->createMock(EntityInterface::class);
    $mock->method('label')->willReturn('foo');
    $mock->method('id')->willReturn('42');
    $this->assertEquals('foo', $mock->label());

    // Ensuring that default overriding is not yet available.
    try {
      $mock->method('label')->willReturn('bar');
    }
    catch (MethodNameAlreadyConfiguredException $e) {
      $this->assertInstanceOf(MethodNameAlreadyConfiguredException::class, $e);
    }
    $this->assertNotEquals('bar', $mock->label());

    // Testing custom overriding of the method return value.
    $labelMethod = TestHelpers::getMockedMethod($mock, 'label');
    $labelMethod->willReturn('baz');
    $mock->method('uuid')->willReturn('myUUID');
    $this->assertEquals('baz', $mock->label());
    $this->assertNotEquals('foo', $mock->label());
    $this->assertEquals('42', $mock->id());
    $this->assertEquals('myUUID', $mock->uuid());

    // Testing the second overriding of the method return value.
    $labelMethod->willReturn('qux');
    $this->assertEquals('qux', $mock->label());

    // Testing a next getter and overriding of the method return value.
    $labelMethod2 = TestHelpers::getMockedMethod($mock, 'label');
    $labelMethod2->willReturnArgument(1);
    // Putting coding standards ignore flag to suppress the warning
    // 'Too many arguments to function label().'.
    // @codingStandardsIgnoreStart
    $this->assertEquals('arg1', $mock->label('arg0', 'arg1'));
    // @codingStandardsIgnoreEnd

    // Testing a getter with callback function.
    $idMethod = TestHelpers::getMockedMethod($mock, 'id');
    $idMethod->willReturnCallback(function () {
      return 777;
    });
    $this->assertSame(777, $mock->id());
  }

  /**
   * @covers ::service
   * @covers ::setServices
   */
  public function testAddServices() {
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
