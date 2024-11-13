/**
 * @file
 * Custom Nightwatch command to clear cache bins and tags.
 *
 * This command allows you to clear specific cache bins and tags by making a request
 * to a predefined endpoint. It is useful for ensuring that the cache is in a known
 * state before running tests.
 */

const assertOperationSuccess = require('../Lib/assertOperationSuccess');

module.exports = class ThCacheClear {
  /**
   * Clears cache bins and tags.
   *
   * @param {Object} options
   *   The options for clearing cache.
   * @param {Array} options.bins
   *   The cache bins to clear.
   * @param {Array} options.tags
   *   The cache tags to clear.
   */
  async command({ bins = [], tags = [] } = {}) {
    const endpoint = '/test-helpers-functional/cache-clear';
    const params = {};
    if (bins.length > 0) {
      params.bins = bins.join(',');
    }
    if (tags.length > 0) {
      params.tags = tags.join(',');
    }
    const url =
      Object.keys(params).length > 0
        ? `${endpoint}?${new URLSearchParams(params)}`
        : endpoint;
    await this.api.thDrupalFetchURL(url, (result) => {
      assertOperationSuccess(result.value.body, 'thLogin');
    });
  }
};
