/**
 * @file
 * Defines the ThSetRequestResponseByHash Nightwatch command.
 */

module.exports = class ThSetRequestResponseByHash {
  /**
   * Sets a stored response by hash.
   *
   * @param {string} hash
   *   The hash identifying the stored response.
   * @param {Object} body
   *   The body of the request to be sent.
   * @param {function} callback
   *   The callback function to be executed after the request is sent.
   */
  async command(hash, body, callback) {
    const endpointPrefix =
      '/test-helpers-http-client-mock/set-stored-response/';

    const url = endpointPrefix + hash;
    await this.api.thDrupalFetchURL(url, 'POST', body);

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, {
        status: 0,
      });
    }
  }
};
