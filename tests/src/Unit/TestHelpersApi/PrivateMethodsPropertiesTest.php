<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\test_helpers\Unit\Assets\ClassWithProtectedItemsStub;
use Drupal\Tests\test_helpers\Unit\Assets\StaticClassWithProtectedItemsStub;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests CreateEntityStub API function.
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
#[CoversMethod(TestHelpers::class, 'getPrivateProperty')]
#[CoversMethod(TestHelpers::class, 'getPrivateMethod')]
#[CoversMethod(TestHelpers::class, 'callPrivateMethod')]
class PrivateMethodsPropertiesTest extends UnitTestCase {

  /**
   * Tests the getPrivateProperty() and setPrivateProperty() methods.
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
   * Tests the getPrivateProperty() method.
   */
  public function testProtectedUtilitiesWithStaticClass() {
    $this->assertSame('propertyOneValue', TestHelpers::getPrivateProperty(StaticClassWithProtectedItemsStub::class, 'propertyOne'));
    TestHelpers::setPrivateProperty(StaticClassWithProtectedItemsStub::class, 'propertyOne', 'propertyOneOverridden');
    $this->assertSame('propertyOneOverridden', TestHelpers::getPrivateProperty(StaticClassWithProtectedItemsStub::class, 'propertyOne'));
    $this->assertSame('functionOneResult', TestHelpers::callPrivateMethod(StaticClassWithProtectedItemsStub::class, 'functionOne'));
  }

}
