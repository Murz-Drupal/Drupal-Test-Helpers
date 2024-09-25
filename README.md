# Test Helpers

The module provides API to simplify writing Drupal tests - unit and functional.
Using the API can significantly reduce the amount of code in your tests to cover
all the logic of tested functions, using provided stubs of Drupal core services
and many other helpers.

Basically, the module provides stubs for the most popular Drupal services like
Entity Storage, EntityQuery, Database, Configuration Factory, and many others.
The stubs can emulate the behavior of entities (create, load, save, delete) and
core services, but without the real initialization of Drupal Kernel, a database,
and other persistent storages, all are emulated in the memory.

Additionally, it provides some utility functions to get private properties and
methods from classes, Plugin Definitions from a YAML file, and many more.

Also, it provides helpers for functional and Nightwatch tests, which can
significantly speed up the test execution time.

And to use the Test Helpers API in your project or a contrib module, you don't
even need to install it in Drupal, adding it via composer as a dev dependency
(without installing on production) is enough:
```
composer require --dev 'drupal/test_helpers'
```

- [API Documentation »](https://project.pages.drupalcode.org/test_helpers/)


See the `\Drupal\test_helpers\TestHelpers` class for the main API functions and
read description in PHPDoc comments.

See the `TestHelpers::SERVICES_CUSTOM_STUBS` for the list of the implemented
Drupal core services stubs.

See the `TestHelpers::SERVICES_CORE_INIT` for the list of the Drupal core
services that can be initiated automatically in the unit test context.

See usage examples in the submodule `tests/modules/test_helpers_example` and in
the unit tests in the `tests/src/Unit` directory.

The module also contains submodules to simplify functional and browser testing:

- `test_helpers_functional`: Utilities for functional and Nightwatch tests to
  simplify actions on the backend side like creating users, permissions,
  switching users, install modules, etc. See the separate README.md file in the
  submodule.

- `test_helpers_http_client_mock`: Automates storing the real HTTP responses and
  mock them in tests without doing real outgoing HTTP calls. Useful when you use
  authorized API calls and don't want to store any credentials in tests. See the
  separate README.md file in the submodule.

---

To make all the test submodules available for installing, put this into the
`settings.php` file:
```php
$settings['extension_discovery_scan_tests'] = TRUE;
```

For the full description of the module, visit the [project
page](https://www.drupal.org/project/test_helpers).

Submit bug reports and feature suggestions, or track changes in the [issue
queue](https://www.drupal.org/project/issues/test_helpers).

Project repository mirrors:
[GitHub](https://github.com/Murz-Drupal/Drupal-Test-Helpers),
[GitLab](https://gitlab.com/murz-drupal/test_helpers).


## Requirements

This module does not have any dependency on any other module.


## Installation

You don't need to install this module to Drupal, just having it as a dev
dependency in `composer` is enough.


## Configuration

The module provides no configuration options.


## Maintainers

- Alexey Korepov - [Murz](https://www.drupal.org/u/murz)
