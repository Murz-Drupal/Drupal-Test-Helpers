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
use Drupal\test_helpers\UnitTestHelpers;
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
   * @covers ::getProtectedProperty
   * @covers ::getProtectedMethod
   * @covers ::callProtectedMethod
   */
  public function testProtectedUtilities() {
    $class = new ClassWithProtectedItemsStub();

    try {
      $class->property1;
      $this->fail("Expected error is not thrown.");
    }
    catch (\Error $e) {
      $this->assertEquals(0, $e->getCode());
    }

    $this->assertSame($class->getProperty1(), UnitTestHelpers::getProtectedProperty($class, 'property1'));
    $this->assertNull(UnitTestHelpers::getProtectedProperty($class, 'property2'));

    UnitTestHelpers::setProtectedProperty($class, 'property2', 'bar');
    $this->assertSame('bar', UnitTestHelpers::getProtectedProperty($class, 'property2'));

    $this->assertSame('bar', UnitTestHelpers::callProtectedMethod($class, 'getProperty2'));
    $this->assertSame('bar', UnitTestHelpers::callProtectedMethod($class, 'getPropertyByName', ['property2']));
    $method = UnitTestHelpers::getProtectedMethod($class, 'getPropertyByName');
    $this->assertSame('foo', $method->invoke($class, 'property1'));
  }

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
    $labelMethod = UnitTestHelpers::getMockedMethod($mock, 'label');
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
    $labelMethod2 = UnitTestHelpers::getMockedMethod($mock, 'label');
    $labelMethod2->willReturnArgument(1);
    $this->assertEquals('arg1', $mock->label('arg0', 'arg1'));

    // Testing a getter with callback function.
    $idMethod = UnitTestHelpers::getMockedMethod($mock, 'id');
    $idMethod->willReturnCallback(function () {
      return 777;
    });
    $this->assertSame(777, $mock->id());
  }

  /**
   * @covers ::addServices
   * @covers ::addService
   * @covers ::createService
   */
  public function testAddServices() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject $entityType */
    $entityType = $this->createMock(EntityTypeInterface::class);
    $entityType->method('getSingularLabel')->willReturn('my entity');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityTypeManager */
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getDefinition')->willReturn($entityType);

    UnitTestHelpers::setServices([
      'entity_type.bundle.info' => NULL,
      'renderer' => NULL,
      'string_translation' => NULL,
      'entity_type.manager' => $entityTypeManager,
      'url_generator' => UrlGenerator::class,
    ]);

    // Checking initialized services.
    try {
      $service = UnitTestHelpers::createService(EntityController::class);
      $this->fail("Expected ServiceNotFoundException is not thrown.");
    }
    catch (ServiceNotFoundException $e) {
      $this->assertEquals('You have requested a non-existent service "entity.repository".', $e->getMessage());
    }

    UnitTestHelpers::setServices(['entity.repository']);

    // Testing the behavior on a real service with the 'create' function.
    $service = UnitTestHelpers::createService(EntityController::class);
    $result = $service->addTitle('my_entity');
    $this->assertSame('Add my entity', $result->__toString());

    // Checking resetting of the container.
    UnitTestHelpers::setServices(['entity.repository'], TRUE);
    try {
      $service = UnitTestHelpers::createService(EntityController::class);
      $this->fail('Previous line should throw an exception.');
    }
    catch (ServiceNotFoundException $e) {
      $this->assertStringStartsWith('You have requested a non-existent service', $e->getMessage());
    }

  }

  /**
   * @covers ::createServiceFromYaml
   */
  public function testCreateServiceFromYaml() {
    UnitTestHelpers::service('plugin.manager.language_negotiation_method', $this->createMock(LanguageNegotiationMethodManager::class));
    \Drupal::service('plugin.manager.language_negotiation_method')
      ->method('getDefinitions')
      ->willReturn(['method1', 'method2']);

    $service = UnitTestHelpers::createServiceFromYaml(
      'core/modules/language/language.services.yml',
      'language_negotiator',
      [],
      [
        0 => 'config.factory',
        'language_manager' => $this->createMock(ConfigurableLanguageManagerInterface::class),
        'settings' => new Settings([]),
        'request_stack' => NULL,
      ]
    );
    $this->assertEquals(['method1', 'method2'], $service->getNegotiationMethods());
  }

}
