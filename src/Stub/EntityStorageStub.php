<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\test_helpers\StubFactory\EntityStubFactory;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * A stub of the Drupal's default SqlContentEntityStorage class.
 */
class EntityStorageStub extends SqlContentEntityStorage {

  protected $stubEntities;

  protected $entityStubFactory;

  /**
   * A workaround to fix access to the private method of the parent class.
   */
  protected $baseEntityClass;

  /**
   * Constructs a new EntityStorageStubFactory.
   */
  public function __construct($entityClass, $annotation = '\Drupal\Core\Entity\Annotation\ContentEntityType') {
    $entityType = $this->initEntityDefinition($entityClass, $annotation);
    $this->baseEntityClass = $entityType->getClass();
    $this->entityType = $entityType;
    $this->entityTypeId = $entityType->id();
    $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');

    $this->stubEntityStorageById = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityClass(?string $bundle = NULL): string {
    return $this->baseEntityClass;
  }

  public function invokeHook($hook, EntityInterface $entity) {
  }

  public function loadMultiple(array $ids = NULL) {
    if ($ids === NULL) {
      return $this->stubEntities;
    }
    $entities = [];
    foreach ($ids as $id) {
      if (isset($this->stubEntities[$id])) {
        $entities[$id] = $this->stubEntities[$id];
      }
    }
    return $entities;
  }

  public function loadByProperties(array $values = []) {
    $entities = [];
    if (is_iterable($this->stubEntities)) {
      foreach ($this->stubEntities as $entity) {
        foreach ($values as $key => $value) {
          // Now getting only the `value` property to compare.
          // @todo Try to check the main property and get it.
          if (
            empty($entity->$key)
            || empty($entity->$key->value)
            || $entity->$key->value != $value
          ) {
            continue 2;
          }
        }
        $entities[] = $entity;
      }
    }
    return $entities;
  }

  public function stubGetNewEntityId() {
    // @todo Make detection of id field type, and calculate only for integers.
    $id = max(array_keys($this->stubEntities ?? [0])) + 1;
    // The `id` value for even integer autoincrement is stored as string in
    // Drupal, so we should follow this behaviour too.
    return (string) $id;
  }

  public function stubStoreEntity($entity) {
    $this->stubEntities[$entity->id()] = $entity;
  }

  public function stubDeleteEntityById($id) {
    unset($this->stubEntities[$id]);
  }

  /**
   * Initializes an entity definition and adds to storage. Not working yet.
   */
  public function initEntityDefinition($entityClass, $annotation) {
    $entityTypeDefinition = UnitTestHelpers::getPluginDefinition($entityClass, 'Entity', $annotation);
    $entityTypeId = $entityTypeDefinition->get('id');
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $entityRepository */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $entityTypeDefinition = $entityTypeManager->getDefinition($entityTypeId, FALSE);
    if (!$entityTypeDefinition) {
      $entityTypeDefinition = UnitTestHelpers::getPluginDefinition($entityClass, 'Entity');
      $entityTypeManager->stubSetDefinition($entityTypeId, $entityTypeDefinition);
    }
    return $entityTypeDefinition;
  }

  /**
   * Creates an entity stub with field values.
   *
   * @param string $entityClass
   *   A class path to use when creating the entity.
   * @param array $values
   *   The array of values to set in the created entity.
   * @param array $options
   *   The array of options:
   *   - methods: the list of additional methods to allow mocking of them.
   *   - definitions: the list of custom field definitions for needed fields.
   *     If not passed - the default one (`StringItem`) will be used.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mocked entity object.
   */
  public function stubCreateEntity(string $entityClass, array $values = [], array $options = []) {
    return EntityStubFactory::create($entityClass, $values, $options);
  }

}
