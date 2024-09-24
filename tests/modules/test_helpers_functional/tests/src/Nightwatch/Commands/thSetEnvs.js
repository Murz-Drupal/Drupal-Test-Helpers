const endpoint = '/test-helpers-functional/set-envs';

/**
 * Sets the environment variables on the Drupal side.
 *
 * @param {array} envs
 *   A list of envs with values.
 * @param {function} callback
 *   A callback which will be called, when creating the role is finished.
 * @return {object}
 *   The thLogin command.
 */
exports.command = function thSetEnvs(envs, callback) {
  const self = this;
  const tempUrl = new URL(endpoint, 'http://temp');
  Object.entries(envs).forEach(([key, value]) => {
    tempUrl.searchParams.append(key, value);
  });
  const pathWithParams = tempUrl.pathname + tempUrl.search;
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
