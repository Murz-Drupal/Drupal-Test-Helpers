/**
 * @file
 * Provides a function to assert that an operation was successful.
 */

/**
 * Asserts that an operation was successful.
 *
 * @param {string} data
 *   The data to check.
 * @param {string} operationName
 *   The name of the operation.
 * @param {string} url
 *   The url of the request.
 *
 * @throws {Error}
 *   If the operation was not successful.
 */
module.exports = function assertOperationSuccess(data, operationName, url) {
  let parsed;
  try {
    parsed = JSON.parse(data);
  } catch (error) {
    throw new Error(
      `Unexpected output${operationName ? ` in the ${operationName}` : ''}${url ? ` on the url ${url}` : ''}. Most likely you forgot to enable the "test_helpers:test_helpers_functional" module in the test context. Output: ${data}`,
    );
  }
  if (!parsed.status === 'success') {
    throw new Error(
      `The ${operationName || 'operation'} failed, response: ${JSON.stringify(data)}`,
    );
  }
};
