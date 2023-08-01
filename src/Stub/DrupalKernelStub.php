<?php

namespace Drupal\test_helpers\Stub;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DrupalKernel;

/**
 * A stub of the Drupal's default ConfigurableLanguageManager class.
 */
class DrupalKernelStub extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $environment = NULL,
    $class_loader = NULL,
    $allow_dumping = TRUE,
    $app_root = NULL
  ) {
    $environment ??= 'dev';
    $class_loader ??= new ClassLoader();
    parent::__construct($environment, $class_loader, $allow_dumping, $app_root);
  }

}
