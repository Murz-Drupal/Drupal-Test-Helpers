/**
 * @file
 * Contains the ThInstallModules Nightwatch command.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

module.exports = class ThInstallModules {
  /**
   * Installs one or several modules at once, optionally with dependencies.
   *
   * @param {array} modules
   *   The module machine names to enable.
   * @param {boolean} [enableDependencies=false]
   *   Whether to install dependencies if applicable.
   * @param {function} [callback]
   *   A callback which will be called when the module installation is finished.
   * @return {object}
   *   The thInstallModules command.
   */
  command(modules, enableDependencies = false, callback = undefined) {
    const endpoint = '/test-helpers-functional/module-installer';
    const url = `${endpoint}?install=${modules.join(',')}&enable_dependencies=${
      enableDependencies ? '1' : '0'
    }`;
    this.api
      .thDrupalFetchURL(url, (result) => {
        assertOperationSuccess(result.value.body, 'thInstallModules');
      })
      .perform(() => {
        if (typeof callback === 'function') {
          const self = this;
          callback.call(self);
        }
      });

    return this;
  }
};
