# Test Helpers

Collection of helper classes and functions to use in Drupal Unit tests, Kernel
tests, Functional tests.

The main helper class is EntityStorageStub which allows quickly create an Entity
Stub in Unit test functions, that will be available to load via
`EntityStorageInterface::load()`,
`EntityStorageInterface::loadMultiple()`
and even via `EntityRepository::loadEntityByUuid()`.

Here is an example:

```php
    $node1Values = [
      'type' => 'article',
      'title' => 'My cool article',
      'body' => 'Very interesting article text.',
      'field_tags' => [
        ['target_id' => 1],
        ['target_id' => 3],
      ],
    ];
    $node1Entity = $this->entityStorageStub->createEntityStub(Node::class, $node1Values)
    $node1Entity->save();

    $node1EntityId = $node1Entity->id();
    $node1EntityUuid = $node2Entity->uuid();
    $node1EntityType = $node2Entity->getEntityTypeId();

    $node1LoadedById = \Drupal::service('entity_type.manager')->getStorage('node')->load($node1EntityId);

    $node1LoadedByUuid = \Drupal::service('entity.repository')->loadEntityByUuid($node1EntityType, $node1EntityUuid);
```

More examples you can found in unit test file `tests/src/Unit/EntityStorageStubTest.php`.
