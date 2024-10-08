/**
 * @file
 * Defines the ThGetRequestResponseFromPage Nightwatch command.
 */

module.exports = class ThGetRequestResponseFromPage {
  /**
   * Custom Nightwatch command to get the request response from a page.
   *
   * @param {number} delta
   *   The index of the request hash to retrieve. Defaults to 0.
   * @param {function} callback
   *   Optional callback function to be called with the response content.
   *
   * @return {Promise<string>}
   *   The content of the response.
   */
  async command(delta = 0, callback = undefined) {
    const endpointPrefix =
      '/test-helpers-http-client-mock/get-stored-response/';

    if (arguments.length === 1 && typeof delta === 'function') {
      callback = delta;
      delta = 0;
    }
    const hashes = await this.api.thGetRequestHashesFromPage();
    const hash = hashes[delta];
    const url = endpointPrefix + hash;

    const content = await this.api.thDrupalFetchURL(url);

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, {
        status: 0,
        value: content,
      });
    }

    return content;
  }
};
