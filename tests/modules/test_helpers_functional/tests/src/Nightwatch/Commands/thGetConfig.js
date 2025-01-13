/**
 * @file
 * Nightwatch command to create a new user with optional permissions and login.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

module.exports = class thGetConfig {
  /**
   * Get Drupal configuration values by the configuration name.
   *
   * @param {string} name
   *   The configuration name.
   * @param {Function} [callback=undefined]
   *   The callback function to execute.
   *
   * @return {Object}
   *   The result object containing the response.
   */
  async command(name, callback = undefined) {
    let body;
    const url = `/test-helpers-functional/get-config/${name}`;
    await this.api.thDrupalFetchURL(url, (result) => {
      assertOperationSuccess(result.value.body, 'thGetConfig', url);
      body = JSON.parse(result.value.body);
    });

    this.api.perform(() => {
      if (typeof callback === 'function') {
        const self = this;
        callback.call(self, { status: 0, value: body });
      }
    });
    return body;
  }
};
