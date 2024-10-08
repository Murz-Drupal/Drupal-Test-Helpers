/**
 * @file
 * Contains the ThLogout Nightwatch command.
 */

const assert = require('assert');

module.exports = class ThLogout {
  /**
   * Performs a login for the given user.
   *
   * @param {function} callback
   *   A callback which will be called when the login is finished.
   *
   * @return {object}
   *   The thLogin command.
   */
  command(callback) {
    const endpoint = '/test-helpers-functional/logout';
    this.api
      .thDrupalFetchURL(endpoint, (result) => {
        assert.equal(JSON.parse(result.value.data).status, 'success');
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
