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
   * @covers ::getPrivateProperty
   * @covers ::getPrivateMethod
   * @covers ::callPrivateMethod
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

    $this->assertSame($class->getProperty1(), TestHelpers::getPrivateProperty($class, 'property1'));
    $this->assertNull(TestHelpers::getPrivateProperty($class, 'property2'));

    TestHelpers::setPrivateProperty($class, 'property2', 'bar');
    $this->assertSame('bar', TestHelpers::getPrivateProperty($class, 'property2'));

    $this->assertSame('bar', TestHelpers::callPrivateMethod($class, 'getProperty2'));
    $this->assertSame('bar', TestHelpers::callPrivateMethod($class, 'getPropertyByName', ['property2']));
    $method = TestHelpers::getPrivateMethod($class, 'getPropertyByName');
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
    $this->assertEquals('arg1', $mock->label('arg0', 'arg1'));

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
   * @covers ::createService
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
   * @covers ::createServiceFromYaml
   */
  public function testCreateServiceFromYaml() {
    TestHelpers::service('plugin.manager.language_negotiation_method', $this->createMock(LanguageNegotiationMethodManager::class));
    \Drupal::service('plugin.manager.language_negotiation_method')
      ->method('getDefinitions')
      ->willReturn(['method1', 'method2']);

    $service = TestHelpers::createServiceFromYaml(
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
