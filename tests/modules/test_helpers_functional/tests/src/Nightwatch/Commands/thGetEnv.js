const endpointPrefix = '/test-helpers-functional/get-env/';

/**
 * Gets the environment variable value from the Drupal side.
 *
 * @param {object} env
 *   Env variable names and values.
 * @param {function} callback
 *   A callback which will be called, when creating the role is finished.
 * @return {object}
 *   The thLogin command.
 */
exports.command = function thGetEnv(env, callback) {
  const self = this;
  this.drupalRelativeURL(endpointPrefix + env)
    .waitForElementVisible('body')
    .perform(() => {
      if (typeof callback === 'function') {
        this.getText('body', (result) => {
          callback.call(self, result);
        });
      }
    });

  return this;
};
