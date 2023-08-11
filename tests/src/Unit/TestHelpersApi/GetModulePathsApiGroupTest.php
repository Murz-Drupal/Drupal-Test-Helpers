<?php

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Drupal\test_helpers_example\Controller\TestHelpersExampleController;

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
        '/projects/test_helpers/themes/contrib/some_theme',
        '/projects/test_helpers/themes/contrib/some_theme/src/SomeTheme.php',
        'some_theme',
      ],
      [
        NULL,
        '/projects/test_helpers/no_themes/contrib/some_theme/src/SomeTheme.php',
        'some_theme',
      ],
      [
        NULL,
        '/projects/test_helpers/themes/contrib/some_theme/src/SomeTheme.php',
        'some_another_theme',
      ],
      [
        str_replace('/src/Controller/TestHelpersExampleController.php', '', TestHelpers::getClassFile(TestHelpersExampleController::class)),
        TestHelpersExampleController::class,
        'test_helpers_example',
      ],
      [
        str_replace('/src/Controller/TestHelpersExampleController.php', '', TestHelpers::getClassFile(TestHelpersExampleController::class)),
        TestHelpersExampleController::class,
        NULL,
      ],
      [
        '/sites/test_helpers/www/modules/contrib/helpers/test_helpers/modules/examples/test_helpers_example',
        '/sites/test_helpers/www/modules/contrib/helpers/test_helpers/modules/examples/test_helpers_example/tests/Unit/test.php',
        'test_helpers_example',
      ],
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
