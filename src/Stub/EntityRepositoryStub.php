<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\test_helpers\TestHelpers;

/**
 * A stub of the Drupal's default EntityRepository class.
 */
class EntityRepositoryStub extends EntityRepository {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager = NULL,
    LanguageManagerInterface $language_manager = NULL,
    ContextRepositoryInterface $context_repository = NULL
  ) {
    $entity_type_manager ??= TestHelpers::service('entity_type.manager');
    $language_manager ??= TestHelpers::service('language_manager');
    $context_repository ??= new LazyContextRepository(TestHelpers::getContainer(), []);
    parent::__construct($entity_type_manager, $language_manager, $context_repository);
  }

}
