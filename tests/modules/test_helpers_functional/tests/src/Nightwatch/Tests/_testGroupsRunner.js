/**
 * @file
 * Tests the test_helpers_functional module API.
 *
 * We're trying to combine all tests in a single file to speed up the test
 * execution, because for each separate file Nightwatch performs the clean
 * Drupal installation.
 *
 * After creating a new group with tests, require it here and add to the
 * module.exports object.
 */

const { EventEmitter } = require('events');

const groupUserApiTests = require('./groupUserApiTests');
const groupEnvApiTests = require('./groupEnvApiTests');
const groupModuleInstallerTests = require('./groupModuleInstallerTests');

module.exports = {
  '@tags': ['test_helpers', 'test_helpers_functional'],
  beforeEach() {
    // Increase max listeners for this long running test - a workaround for the
    // issue https://github.com/nightwatchjs/nightwatch/issues/408
    EventEmitter.defaultMaxListeners = 100;
  },
  afterEach() {
    // Reset max listeners to the node.js default once the test is complete.
    EventEmitter.defaultMaxListeners = 10;
  },
  before(browser) {
    browser.drupalInstall({
      installProfile: 'test_helpers_functional_profile',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },

  ...groupUserApiTests,
  ...groupEnvApiTests,
  ...groupModuleInstallerTests,
};
