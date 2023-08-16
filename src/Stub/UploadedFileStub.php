<?php

namespace Drupal\test_helpers\Stub;

use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A helper class to mock UploadedFile objects.
 */
class UploadedFileStub extends UploadedFile {
  /**
   * A static storage for files content.
   *
   * @var \ArrayObject
   */
  private static \ArrayObject $filesStorage;

  /**
   * The full file path.
   *
   * @var string
   */
  private string $filePath;

  /**
   * {@inheritdoc}
   *
   * Additionally to the original constructor, you have to pass the file content
   * via the $content argument.
   */
  public function __construct(string $path, string $originalName, string $mimeType = NULL, int $error = NULL, bool $test = FALSE, $content = '') {
    self::$filesStorage ??= new \ArrayObject();
    $filesStorage = self::$filesStorage;
    // @todo Move to a storage as a container service to clear on recreating
    // a Drupal container.
    $this->filePath = $path;
    $filesStorage[$path] = $content;

    TestHelpers::mockPhpFunction('is_file', UploadedFile::class, function ($filename) use (&$filesStorage) {
      return isset($filesStorage[$filename]);
    });
    parent::__construct($path, $originalName, $mimeType, $error, $test);
  }

  /**
   * {@inheritdoc}
   */
  public function getContent(): string {
    return self::$filesStorage[$this->getRealPath()];
  }

  /**
   * {@inheritdoc}
   *
   * The '#[\ReturnTypeWillChange]' attribute is used to suppress the
   * "Return type of _ the should either be compatible" warning to keep the
   * compatibiltiy with PHP 7.4 and 8.1 together.
   */
  #[\ReturnTypeWillChange]
  public function getRealPath() {
    return $this->filePath ?? FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * The '#[\ReturnTypeWillChange]' attribute is used to suppress the
   * "Return type of _ the should either be compatible" warning to keep the
   * compatibiltiy with PHP 7.4 and 8.1 together.
   */
  #[\ReturnTypeWillChange]
  public function getSize() {
    return strlen(self::$filesStorage[$this->getRealPath()]) ?? FALSE;
  }

  /**
   * Unmocks all mocked php internal functions to get the original behavior.
   *
   * Call it if you need to use original behavior of the UploadedFile class.
   */
  public static function unmockPhpFunctions() {
    TestHelpers::unmockPhpFunction('is_file', UploadedFile::class);
  }

}
