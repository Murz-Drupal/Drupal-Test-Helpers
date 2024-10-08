/**
 * @file
 * Defines the ThGetEnv Nightwatch command.
 */

module.exports = class ThGetEnv {
  /**
   * Gets the environment variable value from the Drupal side.
   *
   * @param {object} env
   *   An object containing environment variable names and values.
   * @param {function} callback
   *   A callback function which will be called when the environment variable
   *   value is retrieved.
   *
   * @return {object}
   *   The ThGetEnv command instance.
   */
  async command(env, callback) {
    const endpointPrefix = '/test-helpers-functional/get-env/';
    let value;
    this.api.thDrupalFetchURL(endpointPrefix + env, (result) => {
      value = result.value.data;
      if (typeof callback === 'function') {
        const self = this;
        callback.call(self, {
          status: 0,
          value,
        });
      }
    });

    return value;
  }
};
