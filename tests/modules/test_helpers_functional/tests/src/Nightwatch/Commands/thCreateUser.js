const endpoint = '/test-helpers-functional/create-user';

/**
 * Creates a new user, with optionally adding permissions and log in.
 *
 * @param {object} userData
 *   An object with user data: name, permissions, etc.
 * @param {boolean} login
 *   Make the log in after creating the user.
 * @param {function} callback
 *   A callback which will be called, when creating the role is finished.
 * @return {object}
 *   The thCreateUser command.
 */
exports.command = function thCreateUser(
  userData,
  login = false,
  callback = undefined,
) {
  const self = this;
  const tempUrl = new URL(endpoint, 'http://temp');
  if (login) {
    userData.__login = 1;
  }
  Object.keys(userData).forEach((key) => {
    tempUrl.searchParams.append(key, userData[key]);
  });
  const pathWithParams = `${tempUrl.pathname}${tempUrl.search}`;
  this.drupalRelativeURL(pathWithParams)
    .waitForElementVisible('body')
    .assert.textContains('body', '"status":"success"');

  this.perform(() => {
    if (typeof callback === 'function') {
      callback.call(self);
    }
  });

  return this;
};
