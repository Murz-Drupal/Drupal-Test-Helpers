/**
 * @file
 * Nightwatch command to create a new user with optional permissions and login.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

module.exports = class thSetConfig {
  /**
   * Sets Drupal configuration values.
   *
   * @param {string} name
   *   The configuration name.
   * @param {object} data
   *   The key-value object with the configuration data to set.
   * @param {Function} [callback=undefined]
   *   The callback function to execute.
   *
   * @return {Object}
   *   The result object containing the response.
   */

  async command(name, data, callback = undefined) {
    let readData;
    const endpoint = `/test-helpers-functional/set-config/${name}`;
    const tempUrl = new URL(endpoint, 'http://temp');
    tempUrl.searchParams.append('data', JSON.stringify(data));
    const url = `${tempUrl.pathname}${tempUrl.search}`;
    await this.api.thDrupalFetchURL(url, (result) => {
      assertOperationSuccess(result.value.body, 'thSetConfig', url);
      readData = JSON.parse(result.value.body).data;
    });

    this.api.perform(() => {
      if (typeof callback === 'function') {
        const self = this;
        callback.call(self, { status: 0, value: readData });
      }
    });

    return readData;
  }
};
