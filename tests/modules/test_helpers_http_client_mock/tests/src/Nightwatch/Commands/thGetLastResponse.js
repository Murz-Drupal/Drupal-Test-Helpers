/**
 * @file
 * Defines the ThGetLastResponse Nightwatch command.
 */

module.exports = class ThGetLastResponse {
  /**
   * Command to get the last response data.
   *
   * @param {int} delta
   *   A delta from the last responses, 0 by default.
   *
   * @param {function} callback
   *   A callback function to be called with the result.
   *
   * @return {Promise<Object>}
   *   A promise that resolves to an object of the response data.
   */
  async command(delta = 0, callback = undefined) {
    const endpoint = `/test-helpers-http-client-mock/get-last-response/${delta}`;

    const response = await this.api.thDrupalFetchURL(endpoint);

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, {
        status: 0,
        value: response,
      });
    }

    return response;
  }
};
