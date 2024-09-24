# Test Helpers Functional

The module provides helper endpoints for Drupal Functional tests:

- `/test-helpers-functional/create-user`: Creates a new user, with optionally
  adding permissions and log-in.
- `/test-helpers-functional/login/{name}`: Performs a login for the given user.
- `/test-helpers-functional/set-envs`: Installs and uninstalls one or several
  modules at once, optionally with dependencies.
- `/test-helpers-functional/get-env/{name}`: Sets the environment variables on
  the Drupal side.
- `/test-helpers-functional/module-installer`: Gets the environment variable
  value from the Drupal side.

And corresponding Nightwatch commands:

- `thCreateUser()`: Creates a new user, with optionally adding permissions and
  log-in.
- `thLogin()`: Performs a login for the given user.
- `thInstallModules()`: Installs one or several modules at once, optionally with
  dependencies.
- `thUninstallModules()`: Uninstalls one or several modules at once.
- `thSetEnvs()`: Sets the environment variables on the Drupal side.
- `thGetEnv()`: Gets the environment variable value from the Drupal side.

Those commands perform actions on the Drupal side, and work many times faster,
than Drupal Core alternatives (`drupalInstall()`, `drupalCreateUser()`, etc).

See the usage examples in the included self-tests in the directory
`tests/modules/test_helpers_functional/tests/src/Nightwatch/Tests`.

Vote for the issue [#3464642 Provide PHP helpers for Nightwatch tests to speed
up routine operations](https://www.drupal.org/project/drupal/issues/3464642) to
port these features in Drupal Core!
