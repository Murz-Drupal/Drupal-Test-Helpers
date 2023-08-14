<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\test_helpers\Unit\TestHelpersApi\TestStubNamespace\TestStub;
use Drupal\Tests\UnitTestCase;

/**
 * Tests utility functions.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class MockPhpFunctionTest extends UnitTestCase {

  /**
   * @covers ::mockPhpFunction
   * @covers ::mockPhpFunctionStorage
   * @covers ::mockPhpFunctionStorage
   * @covers \Drupal\test_helpers\lib\MockedFunctionCalls::_construct
   */
  public function testMockPhpFunction() {
    $calls = TestHelpers::mockPhpFunction(
      'file_put_contents',
      TestStub::class
    );
    $params1 = ['php://stdout', 'foo'];
    $params2 = ['php://stderr', 'bar'];
    $result1 = TestStub::callFilePutContents(...$params1);
    $result2 = TestStub::callFilePutContents(...$params2);
    $this->assertNull($result1);
    $this->assertNull($result2);
    $this->assertCount(2, $calls);
    $this->assertEquals($params1, $calls[0]);
    $this->assertEquals($params2, $calls[1]);

    $calls = TestHelpers::mockPhpFunction(
      'implode',
      TestStub::class,
      function (string $separator, array $array) {
        return 'mocked:' . \implode($separator, $array);
      }
    );
    $testStub = new TestStub();
    $params1 = [',', ['foo', 'bar']];
    $params2 = ['+', ['foo', 'bar', 'baz']];
    $params3 = ['--', ['bar', 'baz']];
    $result1 = $testStub->callImplode(...$params1);
    $result2 = $testStub->callImplode(...$params2);
    $result3 = $testStub->callImplode(...$params3);
    $this->assertEquals('mocked:foo,bar', $result1);
    $this->assertEquals('mocked:foo+bar+baz', $result2);
    $this->assertEquals('mocked:bar--baz', $result3);
    $this->assertCount(3, $calls);
    $this->assertEquals($params1, $calls[0]);
    $this->assertEquals($params2, $calls[1]);
    $this->assertEquals($params3, $calls[2]);
  }

  /**
   * @covers ::mockPhpFunction
   * @covers ::unmockPhpFunction
   * @covers ::unmockAllPhpFunctions
   * @covers ::mockPhpFunctionStorage
   */
  public function testMockPhpFunctionRemocking() {
    $testStub = new TestStub();
    $implodeParams = [',', ['foo', 'bar']];
    $explodeParams = [',', 'foo,bar'];
    TestHelpers::mockPhpFunction(
      'implode',
      TestStub::class,
      function (string $separator, array $array) {
        return 'mocked1:' . \implode($separator, $array);
      }
    );
    TestHelpers::mockPhpFunction(
      'explode',
      TestStub::class,
      function (string $separator, string $string) {
        $result = \explode($separator, $string);
        $result[] = 'mocked';
        return $result;
      }
    );

    $this->assertEquals('mocked1:foo,bar', $testStub->callImplode(...$implodeParams));
    $this->assertEquals(['foo', 'bar', 'mocked'], $testStub->callExplode(...$explodeParams));

    TestHelpers::mockPhpFunction(
      'implode',
      TestStub::class,
      function (string $separator, array $array) {
        return 'mocked2:' . \implode($separator, $array);
      }
    );
    $this->assertEquals('mocked2:foo,bar', $testStub->callImplode(...$implodeParams));

    TestHelpers::unmockPhpFunction('implode', TestStub::class);
    $this->assertEquals('foo,bar', $testStub->callImplode(...$implodeParams));
    $this->assertEquals(['foo', 'bar', 'mocked'], $testStub->callExplode(...$explodeParams));

    TestHelpers::unmockAllPhpFunctions();
    $this->assertEquals('foo,bar', $testStub->callImplode(...$implodeParams));
    $this->assertEquals(['foo', 'bar'], $testStub->callExplode(...$explodeParams));
  }

}


namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi\TestStubNamespace;

/**
 * A helper class to test PHP funcitons mocking.
 */
class TestStub {

  /**
   * Calls the file_put_contents function.
   */
  public static function callFilePutContents() {
    $args = func_get_args();
    return file_put_contents(...$args);
  }

  /**
   * Calls the implode function.
   */
  public function callImplode() {
    $args = func_get_args();
    return implode(...$args);
  }

  /**
   * Calls the explode function.
   */
  public function callExplode() {
    $args = func_get_args();
    return explode(...$args);
  }

}
