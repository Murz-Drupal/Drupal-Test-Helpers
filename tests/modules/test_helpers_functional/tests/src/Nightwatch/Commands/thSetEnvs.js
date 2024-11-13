/**
 * @file
 * Defines the ThSetEnvs Nightwatch command.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

module.exports = class ThSetEnvs {
  /**
   * Sets the environment variables on the Drupal side.
   *
   * @param {Object} envs
   *   A list of environment variables with their values.
   * @param {Function} callback
   *   A callback which will be called when setting the environment variables is finished.
   *
   * @return {Object}
   *   The ThSetEnvs command.
   */
  command(envs, callback) {
    const endpoint = '/test-helpers-functional/set-envs';
    const tempUrl = new URL(endpoint, 'http://temp');
    Object.entries(envs).forEach(([key, value]) => {
      tempUrl.searchParams.append(key, value);
    });
    const pathWithParams = tempUrl.pathname + tempUrl.search;
    this.api.thDrupalFetchURL(pathWithParams, (result) => {
      assertOperationSuccess(result.value.body, 'thSetEnvs');
    });

    this.api.perform(() => {
      if (typeof callback === 'function') {
        const self = this;
        callback.call(self);
      }
    });

    return this;
  }
};
