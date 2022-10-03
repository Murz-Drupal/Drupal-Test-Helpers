<?php

namespace Drupal\test_helpers;

use Drupal\Core\Entity\EntityFieldManager;

/**
 * The EntityFieldManagerStubFactory class.
 */
class EntityFieldManagerStubFactory {

  /**
   * Constructs a new EntityFieldManagerStubFactory.
   */
  public function __construct() {
    $this->unitTestCaseApi = UnitTestCaseApi::getInstance();
  }

  /**
   * Creates the EntityFieldManagerStubFactory instance.
   */
  public function createInstance() {

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityFieldManager */
    $entityFieldManager = $this->unitTestCaseApi->createPartialMock(EntityFieldManager::class, [
      'stubSetBaseFieldDefinitons',
      'stubSetFieldDefinitons',
    ]);
    UnitTestHelpers::bindClosureToClassMethod(
      function ($entityTypeId, $baseFieldDefinitions) {
        $this->baseFieldDefinitions[$entityTypeId] = $baseFieldDefinitions;
      },
      $entityFieldManager,
      'stubSetBaseFieldDefinitons'
    );
    UnitTestHelpers::bindClosureToClassMethod(
      function ($entityTypeId, $bundle, $fieldDefinitions) {
        // @todo Get a proper langcode.
        $langcode = 'en';
        $this->baseFieldDefinitions[$entityTypeId][$bundle][$langcode] = $fieldDefinitions;
      },
      $entityFieldManager,
      'stubSetFieldDefinitons'
    );
    UnitTestHelpers::addToContainer('entity_field.manager', $entityFieldManager);

    return $entityFieldManager;
  }

}
