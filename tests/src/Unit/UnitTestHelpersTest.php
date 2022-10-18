<?php

namespace Drupal\Tests\test_helpers\Unit;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\UrlGenerator;
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

    // Testing a getter with callback function
    $idMethod = UnitTestHelpers::getMockedMethod($mock, 'id');
    $idMethod->willReturnCallback(function () {
      return 777;
    });
    $this->assertSame(777, $mock->id());
  }

  /**
   * @covers ::initServices
   */
  public function testInitServices() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject $entityType */
    $entityType = $this->createMock(EntityTypeInterface::class);
    $entityType->method('getSingularLabel')->willReturn('my entity');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityTypeManager */
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getDefinition')->willReturn($entityType);

    UnitTestHelpers::initServices([
      'entity_type.manager' => $entityTypeManager,
      'entity_type.bundle.info',
      'renderer',
      'string_translation',
      'url_generator' => UrlGenerator::class,
    ]);

    // Checking initialized services.
    try {
      $service = UnitTestHelpers::doTestCreateAndConstruct(EntityController::class);
      $this->fail('Previous line should throw an exception.');
    }
    catch (ServiceNotFoundException $e) {
      $this->assertEquals('You have requested a non-existent service "entity.repository".', $e->getMessage());
    }

    UnitTestHelpers::initServices(['entity.repository']);

    // Testing the behavior on a real service with the 'create' function.
    $service = UnitTestHelpers::doTestCreateAndConstruct(EntityController::class);
    $result = $service->addTitle('my_entity');
    $this->assertSame('Add my entity', $result->__toString());

    // Checking resetting of the container.
    UnitTestHelpers::initServices(['entity.repository'], TRUE);
    try {
      $service = UnitTestHelpers::doTestCreateAndConstruct(EntityController::class);
      $this->fail('Previous line should throw an exception.');
    }
    catch (ServiceNotFoundException $e) {
      $this->assertStringStartsWith('You have requested a non-existent service', $e->getMessage());
    }

  }

}
