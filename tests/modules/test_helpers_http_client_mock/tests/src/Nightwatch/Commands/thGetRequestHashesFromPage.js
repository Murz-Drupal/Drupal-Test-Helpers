/**
 * @file
 * Defines the ThGetRequestHashesFromPage Nightwatch command.
 */

module.exports = class ThGetRequestHashesFromPage {
  /**
   * Retrieves request hashes from the page's meta tag.
   *
   * @param {function} callback
   *   A callback function to be called with the retrieved hashes.
   *
   * @return {Promise<Array>}
   *   A promise that resolves to an array of request hashes.
   */
  async command(callback) {
    const requestHashesSelector =
      'meta[name="TestHelpersHttpClientMockRequestsHashes"]';
    const hashesString = await this.api.getAttribute(
      requestHashesSelector,
      'content',
    );
    const hashes = JSON.parse(hashesString);

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, {
        status: 0,
        value: hashes,
      });
    }

    return hashes;
  }
};
