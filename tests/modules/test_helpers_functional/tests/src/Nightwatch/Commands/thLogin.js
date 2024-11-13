/**
 * @file
 * Contains the ThLogin Nightwatch command.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

module.exports = class ThLogin {
  /**
   * Performs a login for the given user.
   *
   * @param {string} username
   *   The name of a user to log in.
   * @param {function} callback
   *   A callback which will be called when the login is finished.
   *
   * @return {object}
   *   The thLogin command.
   */
  command(username, callback) {
    const endpoint = '/test-helpers-functional/login/';
    const url = endpoint + username;
    this.api
      .thDrupalFetchURL(url, (result) => {
        assertOperationSuccess(result.value.body, 'thLogin');
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
