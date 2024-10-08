# Test Helpers Functional

The module provides Nightwatch commands to simplify your tests:

- `thCreateUser()`: Creates a new user, with optionally adding permissions and
  log-in.
- `thLogin()`: Performs a login for the given user.
- `thLogout()`: Performs a logout for the current user.
- `thInstallModules()`: Installs one or several modules at once, optionally with
  dependencies.
- `thUninstallModules()`: Uninstalls one or several modules at once.
- `thDrupalFetchURL()`: Fetch a Drupal relative URL directly, with headers and
  cookies, but in the background, without touching the browser state.
- `thCacheClear()`: Clears the Drupal cache - full, or per tag, per bin.
- `thSetEnvs()`: Sets the environment variables on the Drupal side.
- `thGetEnv()`: Gets the environment variable value from the Drupal side.

Those commands perform actions on the Drupal side, and work many times faster,
than Drupal Core alternatives (`drupalInstall()`, `drupalCreateUser()`, etc) and
keep the current browser page state untouched, running the requests in the
background.

See the usage examples in the included self-tests in the directory
`tests/modules/test_helpers_functional/tests/src/Nightwatch/Tests`.

Vote for the issue [#3464642 Provide PHP helpers for Nightwatch tests to speed
up routine operations](https://www.drupal.org/project/drupal/issues/3464642) to
port these features in Drupal Core!
