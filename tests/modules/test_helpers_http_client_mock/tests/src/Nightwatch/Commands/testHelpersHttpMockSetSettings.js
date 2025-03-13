const assert = require('assert');

const stubSetSettingsPath = '/test-helpers-http-client-mock/set-settings';

exports.command = function testHelpersHttpMockSetSettings(
  settings = {},
  callback = undefined,
) {
  const urlParams = new URLSearchParams();
  urlParams.append('settings', JSON.stringify(settings));
  const requestPath = `${stubSetSettingsPath}?${urlParams.toString()}`;
  this.thDrupalFetchURL(requestPath, (result) => {
    let response;
    try {
      response = JSON.parse(result.value.body);
    } catch (e) {
      assert.fail(`Failed to parse JSON response: ${result.value.body}`);
    }
    assert.equal(response.status, 'success');
  }).perform(() => {
    if (typeof callback === 'function') {
      const self = this;
      callback.call(self);
    }
  });

  return this;
};
