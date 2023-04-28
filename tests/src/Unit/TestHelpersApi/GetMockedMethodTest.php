<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;

/**
 * Tests UnitTestHelpers functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class GetMockedMethodTest extends UnitTestCase {

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

}
