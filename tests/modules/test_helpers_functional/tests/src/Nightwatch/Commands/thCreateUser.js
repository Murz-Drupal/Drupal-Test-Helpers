/**
 * @file
 * Nightwatch command to create a new user with optional permissions and login.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

module.exports = class ThCreateUser {
  /**
   * Creates a new user, with optionally adding permissions and log in.
   *
   * @param {object} userData
   *   An object with user data: name, permissions, etc.
   * @param {boolean} [login=false]
   *   Whether to log in after creating the user.
   * @param {function} [callback]
   *   A callback which will be called when creating the role is finished.
   * @return {object}
   *   The thCreateUser command.
   */
  command(userData, login = false, callback = undefined) {
    const endpoint = '/test-helpers-functional/create-user';
    const tempUrl = new URL(endpoint, 'http://temp');
    if (login) {
      userData.__login = 1;
    }
    Object.keys(userData).forEach((key) => {
      tempUrl.searchParams.append(key, userData[key]);
    });
    const pathWithParams = `${tempUrl.pathname}${tempUrl.search}`;
    this.api.thDrupalFetchURL(pathWithParams, (result) => {
      assertOperationSuccess(result.value.body, 'thLogin');
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
