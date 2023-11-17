<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

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
    $filePath = TestHelpers::getClassFile(TestHelpers::class);
    $currentModulePath = str_replace('/src/TestHelpers.php', '', $filePath);
    $coreFilePath = TestHelpers::getClassFile(\Drupal::class);
    $corePath = str_replace('/lib/Drupal.php', '', $coreFilePath);
    $testSets = [
      [
        $corePath,
        PhpTransliteration::class,
        'core',
      ],
      [
        $currentModulePath,
        NULL,
        NULL,
      ],
      [
        $currentModulePath . '/tests/src/Unit/Assets/test_helpers_test1_module',
        $currentModulePath . '/tests/src/Unit/Assets/test_helpers_test1_module/src/Lib/MyLib.php',
        'test_helpers_test1_module',
      ],
      [
        $currentModulePath . '/tests/src/Unit/Assets/test_helpers_test1_module_0',
        $currentModulePath . '/tests/src/Unit/Assets/test_helpers_test1_module_0/MyLib.php',
        'test_helpers_test1_module',
      ],
      [
        NULL,
        $currentModulePath . '/tests/src/Unit/Assets/test_helpers_test1_module_0/src/lib.inc',
        'test_helpers_test1_module_0',
      ],
    ];

    foreach ($testSets as $set) {
      $this->assertEquals($set[0], TestHelpers::getModuleRoot($set[1], $set[2]));
    }
  }

  /**
   * @covers ::getModuleName
   */
  public function testGetModuleName() {
    $testSets = [
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
      [
        'core',
        PhpTransliteration::class,
      ],
      [
        'test_helpers',
        NULL,
      ],
      [
        'test_helpers',
        0,
      ],
    ];

    foreach ($testSets as $set) {
      $this->assertEquals($set[0], TestHelpers::getModuleName($set[1]));
    }
  }

  /**
   * @covers ::getCallerInfo
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
   * @covers ::getDrupalRoot
   */
  public function testGetDrupalRoot() {
    $path = TestHelpers::getDrupalRoot();
    $this->assertTrue(file_exists($path . '/core/lib/Drupal.php'));
  }

  /**
   * @covers ::getModuleFilePath
   */
  public function testGetModuleFilePath() {
    $path = TestHelpers::getModuleFilePath('test_helpers.info.yml');
    $this->assertTrue(file_exists($path));
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
