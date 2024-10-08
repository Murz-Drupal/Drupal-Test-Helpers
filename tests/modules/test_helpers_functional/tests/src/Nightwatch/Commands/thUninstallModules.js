/**
 * @file
 * Contains the thUninstallModules Nightwatch command.
 */

const assert = require('assert');

/**
 * Uninstalls one or several modules at once.
 *
 * @param {array} modules
 *   The module machine names to uninstall.
 * @param {function} callback
 *   A callback which will be called when the uninstallation is finished.
 * @return {object}
 *   The thUninstallModules command.
 */
exports.command = function thUninstallModules(modules, callback) {
  const endpoint = '/test-helpers-functional/module-installer';
  const url = `${endpoint}?uninstall=${modules.join(',')}`;

  /**
   * Fetches the URL to uninstall the modules.
   *
   * @param {object} result
   *   The result object from the fetch.
   */
  this.thDrupalFetchURL(url, (result) => {
    assert.equal(JSON.parse(result.value.data).status, 'success');
  });

  /**
   * Performs the callback function if provided.
   */
  this.perform(() => {
    if (typeof callback === 'function') {
      const self = this;
      callback.call(self);
    }
  });

  return this;
};
