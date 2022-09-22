# Test Helpers

Collection of helper classes and functions to use in Drupal Unit tests, Kernel
tests, Functional tests.

## EntityStubFactory

The main helper class is EntityStubFactory which allows quickly create an Entity
Stub in Unit test functions, that will be immediately available to get via
storage functions:
- `EntityStorageInterface::load()`,
- `EntityStorageInterface::loadMultiple()`
- `EntityStorageInterface::loadByProperties()`
- `EntityRepository::loadEntityByUuid()`.

Here is an example:

```php
/** use Drupal\test_helpers\EntityStubFactory; */
$entityStubFactory = new EntityStubFactory();

$node1Values = [
  'type' => 'article',
  'title' => 'My cool article',
  'body' => 'Very interesting article text.',
  'field_tags' => [
    ['target_id' => 1],
    ['target_id' => 3],
  ],
];
$node1Entity = $entityStubFactory->create(Node::class, $node1Values);
$node1Entity->save();

$node1EntityId = $node1Entity->id();
$node1EntityUuid = $node1Entity->uuid();
$node1EntityType = $node1Entity->getEntityTypeId();

$node1LoadedById = \Drupal::service('entity_type.manager')->getStorage('node')->load($node1EntityId);

$node1LoadedByUuid = \Drupal::service('entity.repository')->loadEntityByUuid($node1EntityType, $node1EntityUuid);

$this->assertEquals(1, $node1LoadedById->id());
$this->assertEquals(1, preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $node1LoadedByUuid->uuid()));
```

More examples can be found in the unit test file: `tests/src/Unit/EntityStorageStubApiTest.php`.

## UnitTestHelpers

Class `UnitTestHelpers` provides some utility functions:

- `getAccessibleMethod()`: Gets an accessible method from class using reflection.
- `getPluginDefinition()`: Parses the annotation for a Drupal Plugin class and generates a definition.
- `addToContainer()`: Adds a new service to the Drupal container, if exists - reuse existing.
- `getFromContainerOrCreate()`: Gets the service from the Drupal container, or creates a new one.
- `bindClosureToClassMethod()`: Binds a closure function to a mocked class method.

_It's yet in the early stage of development, so some features are implemented in ugly ways, "just to make them work as needed"._
