<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\test_helpers\Unit\Assets\ClassWithProtectedItemsStub;
use Drupal\Tests\test_helpers\Unit\Assets\StaticClassWithProtectedItemsStub;
use Drupal\Tests\UnitTestCase;

/**
 * Tests CreateEntityStub API function.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class PrivateMethodsPropertiesTest extends UnitTestCase {

  /**
   * @covers ::getPrivateProperty
   * @covers ::getPrivateMethod
   * @covers ::callPrivateMethod
   */
  public function testProtectedUtilitiesWithClass() {
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
   * @covers ::getPrivateProperty
   * @covers ::getPrivateMethod
   * @covers ::callPrivateMethod
   */
  public function testProtectedUtilitiesWithStaticClass() {
    $this->assertSame('propertyOneValue', TestHelpers::getPrivateProperty(StaticClassWithProtectedItemsStub::class, 'propertyOne'));
    TestHelpers::setPrivateProperty(StaticClassWithProtectedItemsStub::class, 'propertyOne', 'propertyOneOverrided');
    $this->assertSame('propertyOneOverrided', TestHelpers::getPrivateProperty(StaticClassWithProtectedItemsStub::class, 'propertyOne'));
    $this->assertSame('functionOneResult', TestHelpers::callPrivateMethod(StaticClassWithProtectedItemsStub::class, 'functionOne'));
  }

}
