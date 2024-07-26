<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A stub of the Drupal's default KeyValueFactory class.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class KeyValueFactoryStub extends KeyValueFactory {

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, array $options = []) {
    TestHelpers::service('keyvalue.database');
    parent::__construct($container, $options);
  }

}
