/**
 * @file
 * Provides before and after functions for Nightwatch tests.
 */

const { EventEmitter } = require('events');

const profile = 'test_helpers_functional_profile';

/**
 * Returns an object containing before and after functions for Nightwatch tests.
 *
 * @param {boolean} [forceInstall=false]
 *   Whether to force the installation of the Drupal site.
 * @param {boolean} [forceUninstall=false]
 *   Whether to force the uninstallation of the Drupal site.
 *
 * @return {Object}
 *   An object containing the before and after functions.
 */
module.exports = function getBeforeAfterFunctions(
  forceInstall = false,
  forceUninstall = false,
) {
  return {
    '@tags': ['test_helpers', 'test_helpers_functional'],

    /**
     * Function to run before the tests.
     *
     * @param {Object} browser
     *   The Nightwatch browser object.
     */
    before(browser) {
      // Increase max listeners for this long running test - a workaround for the
      // issue https://github.com/nightwatchjs/nightwatch/issues/408
      EventEmitter.defaultMaxListeners = 100;

      // Installing new system only if really needed, to speed up tests.
      if (forceInstall) {
        if (browser.globals.drupalDbPrefix) {
          browser.drupalUninstall();
        }
      }
      if (!browser.globals.drupalDbPrefix) {
        browser.drupalInstall({ installProfile: profile });
      }
    },

    /**
     * Function to run after the tests.
     *
     * @param {Object} browser
     *   The Nightwatch browser object.
     */
    after(browser) {
      if (forceUninstall) {
        browser.drupalUninstall();
      }
      // Reset max listeners to the node.js default once the test is complete.
      EventEmitter.defaultMaxListeners = 10;
    },
  };
};
