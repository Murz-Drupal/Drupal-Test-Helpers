/**
 * @file
 * Defines the ThGetLastRequestsHashes Nightwatch command.
 */

module.exports = class ThGetLastRequestsHashes {
  /**
   * Command to get the last requests hashes.
   *
   * @param {function} callback
   *   A callback function to be called with the result.
   *
   * @return {Promise<Array>}
   *   A promise that resolves to an array of request hashes.
   */
  async command(callback) {
    const endpoint = '/test-helpers-http-client-mock/get-last-requests-hashes';

    const response = await this.api.thDrupalFetchURL(endpoint);
    const hashes = JSON.parse(response.data);

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
