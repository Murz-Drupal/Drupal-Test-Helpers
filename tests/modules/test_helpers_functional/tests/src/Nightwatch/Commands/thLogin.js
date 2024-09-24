const endpoint = '/test-helpers-functional/login/';

/**
 * Performs a login for the given user.
 *
 * @param {string} username
 *   The name of a user to log in.
 * @param {function} callback
 *   A callback which will be called, when creating the role is finished.
 * @return {object}
 *   The thLogin command.
 */
exports.command = function thLogin(username, callback) {
  const self = this;
  const url = endpoint + username;
  this.drupalRelativeURL(url)
    .waitForElementVisible('body')
    .assert.textContains('body', '"status":"success"');

  this.perform(() => {
    if (typeof callback === 'function') {
      callback.call(self);
    }
  });

  return this;
};
