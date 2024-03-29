# Test Helpers

The module provides API to simplify writing Drupal unit tests. Using the API can
significantly reduce the amount of code in your unit tests to cover all the
logic of tested functions, using provided stubs of Drupal services.

Basically, the module provides stubs for the most popular Drupal services like
Entity Storage, EntityQuery, Database, Configuration Factory, and many others.
The stubs can emulate the behavior of entities (create, load, save, delete) and
core services, but without the real initialization of Drupal Kernel, a database,
and other persistent storages, all are emulated in the memory.

Additionally, it provides some utility functions to get private properties and
methods from classes, Plugin Definitions from a YAML file, and many more.

And to use the Test Helpers API in your project or a contrib module, you don't
even need to install it in Drupal, adding it via composer as a dev dependency
(without installing on production) is enough:

```
composer require --dev 'drupal/test_helpers'
```

API Documentation is available here:
https://project.pages.drupalcode.org/test_helpers/

See the `\Drupal\test_helpers\TestHelpers` class for the main API functions.

See usage examples in the submodule `test_helpers_example`.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/test_helpers).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/test_helpers).


## Requirements

This module does not have any dependency on any other module.


## Installation

You don't need to install this module to Drupal, just having it as a dev
dependency in `composer` is enough.


## Configuration

The module provides no configuration options.


## Maintainers

- Alexey Korepov - [Murz](https://www.drupal.org/u/murz)
