<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;

/**
 * Tests CreateEntityStub API function.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class GetModulePathsApiGroupTest extends UnitTestCase {

  /**
   * @covers ::getModuleRoot
   */
  public function testGetModuleRoot() {
    $testPairs = [
      [
        '/var/www/html/docroot/modules/contrib/my_module',
        '/var/www/html/docroot/modules/contrib/my_module/src/TestHelpers.php',
        'my_module',
      ],
      [
        '/projects/test_helpers/modules/contrib/helpers/test_helpers',
        '/projects/test_helpers/modules/contrib/helpers/test_helpers/src/TestHelpers.php',
        'test_helpers',
      ],
      [
        '/sites/test_helpers/www/modules/contrib/helpers/test_helpers/modules/examples/test_helpers_example',
        '/sites/test_helpers/www/modules/contrib/helpers/test_helpers/modules/examples/test_helpers_example/tests/Unit/test.php',
        'test_helpers_example',
      ],
    ];

    foreach ($testPairs as $testPair) {
      $this->assertEquals($testPair[0], TestHelpers::getModuleRoot($testPair[1], $testPair[2]));
    }
  }

  /**
   * @covers ::getModuleName
   */
  public function testGetModuleName() {
    $testPairs = [
      [
        'my_module',
        'Drupal\my_module\Controller',
      ],
      [
        'test_helpers',
        'Drupal\Tests\test_helpers\Unit',
      ],
      [
        'test_helpers',
        'Drupal\Tests\test_helpers\Unit\UnitTestHelpersApi',
      ],
    ];

    foreach ($testPairs as $testPair) {
      $this->assertEquals($testPair[0], TestHelpers::getModuleName($testPair[1]));
    }
  }

  /**
   * @covers ::getCallerFile
   */
  public function testGetCallerFile() {
    $parentCaller = DRUPAL_ROOT . '/sites/simpletest/TestCase.php';
    $this->assertEquals([
      'file' => __FILE__,
      'function' => 'testGetCallerFile',
      'class' => 'Drupal\Tests\test_helpers\Unit\TestHelpersApi\GetModulePathsApiGroupTest',
    ], TestHelpers::getCallerInfo(1));
    $this->assertEquals([
      'file' => $parentCaller,
      'function' => 'runTest',
      'class' => 'PHPUnit\Framework\TestCase',
    ], TestHelpers::getCallerInfo(2));
    $this->assertEquals([
      'file' => $parentCaller,
      'function' => 'runTest',
      'class' => 'PHPUnit\Framework\TestCase',
    ], TestHelpers::getCallerInfo());

    $this->assertEquals([
      'file' => __FILE__,
      'function' => 'testGetCallerFile',
      'class' => 'Drupal\Tests\test_helpers\Unit\TestHelpersApi\GetModulePathsApiGroupTest',
    ], $this->testCallerHelper1());

    $this->assertEquals([
      'file' => __FILE__,
      'function' => 'testCallerHelper2',
      'class' => 'Drupal\Tests\test_helpers\Unit\TestHelpersApi\GetModulePathsApiGroupTest',
    ], $this->testCallerHelper2());
    $this->assertEquals([
      'file' => __FILE__,
      'function' => __FUNCTION__,
      'class' => 'Drupal\Tests\test_helpers\Unit\TestHelpersApi\GetModulePathsApiGroupTest',
    ], $this->testCallerHelper2(3));
  }

  /**
   * A helper function level 1 to test getCallerFile.
   *
   * @param mixed $level
   *   The level.
   *
   * @return string|null
   *   The result of the called function.
   */
  private function testCallerHelper1($level = 2) {
    return TestHelpers::getCallerInfo($level);
  }

  /**
   * A helper function level 1 to test getCallerFile.
   *
   * @param mixed $level
   *   The level.
   *
   * @return string|null
   *   The result of the called function.
   */
  private function testCallerHelper2($level = 2) {
    return $this->testCallerHelper1($level);
  }

}
