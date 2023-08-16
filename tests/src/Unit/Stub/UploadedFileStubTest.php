<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\UploadedFileStub;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests DateFormatterStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\DateFormatterStub
 * @group test_helpers
 */
class UploadedFileStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::getContent
   * @covers ::getRealPath
   * @covers ::getSize
   * @covers ::unmockPhpFunctions
   */
  public function testUploadedFileStub() {
    $path = '/some/path/to/file_0.json';
    $name = 'file.json';
    $content = '{"foo":"bar"}';
    $mimeType = 'application/json';
    $stub = new UploadedFileStub($path, $name, $mimeType, NULL, TRUE, $content);
    $this->assertEquals($content, $stub->getContent());
    $this->assertEquals($name, $stub->getClientOriginalName());
    $this->assertEquals($path, $stub->getRealPath());
    $this->assertEquals(strlen($content), $stub->getSize());

    // Test unmockPhpFunctions via using an original class.
    // No exception should be thrown, because we've mocked the 'is_file'
    // function.
    new UploadedFile($path, $name, $mimeType, NULL, TRUE);
    UploadedFileStub::unmockPhpFunctions();
    TestHelpers::assertException(function () use ($path, $name, $mimeType) {
      new UploadedFile($path, $name, $mimeType, NULL, TRUE);
    });
  }

}
