# Test Helpers

The module provides API to simplify writing Drupal unit tests. Using the API can
significantly reduce the amount of code in your unit tests to cover all the
logic of tested functions, using provided stubs of Drupal services.

Basically, the module provides stubs for the most popular Drupal services like
Entity Storage, EntityQuery, Database, Configuration Factory, and many others.
The stubs can emulate the behavior of entities (create, load, save, delete) and
core services, but without the real initialization of Drupal Core, database and
other persistent storages, all are emulated in the memory.

Additionally, it provides some utility functions to get private properties and
methods from classes, Plugin Definitions from a YAML file, and many more.

See the `\Drupal\test_helpers\TestHelpers` class for the main API functions.

See usage examples in the submodule `test_helpers_example`.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/test_helpers).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/test_helpers).


## Requirements

This module does not have any dependency on any other module.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module currently provides no configuration options.


## Maintainers

- Alexey Korepov - [Murz](https://www.drupal.org/u/murz)
