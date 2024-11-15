/**
 * @file
 * Contains the ThGetRequestResponseByHash Nightwatch command.
 */

module.exports = class ThGetRequestResponseByHash {
  /**
   * Command to get the response body by hash.
   *
   * @param {string} hash
   *   The hash to identify the stored response.
   * @param {function} callback
   *   The callback function to execute after fetching the response.
   *
   * @return {Promise<Object>}
   *   The response body.
   */
  async command(hash, callback) {
    const endpointPrefix =
      '/test-helpers-http-client-mock/get-stored-response/';

    const url = endpointPrefix + hash;
    const response = await this.api.thDrupalFetchURL(url);
    const body = response.body;

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, {
        status: 0,
        value: body,
      });
    }

    return body;
  }
};
