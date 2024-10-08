/**
 * @file
 * Contains the ThGetRequestMetadataByHash Nightwatch command.
 */

module.exports = class ThGetRequestMetadataByHash {
  /**
   * Command to get request metadata by hash.
   *
   * @param {string} hash
   *   The hash to get the metadata for.
   * @param {function} callback
   *   The callback function to execute after fetching the metadata.
   *
   * @return {Object}
   *   The metadata object.
   */
  async command(hash, callback) {
    const endpointPrefix =
      '/test-helpers-http-client-mock/get-stored-response/';

    const url = `${endpointPrefix + hash}?metadata=true`;
    let response;
    await this.api.thDrupalFetchURL(url, 'GET', null, (result) => {
      response = result;
    });
    const body = JSON.parse(response.value.data);

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
