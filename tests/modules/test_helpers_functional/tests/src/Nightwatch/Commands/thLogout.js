/**
 * @file
 * Contains the ThLogout Nightwatch command.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

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
        assertOperationSuccess(result.value.body, 'thLogout');
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
