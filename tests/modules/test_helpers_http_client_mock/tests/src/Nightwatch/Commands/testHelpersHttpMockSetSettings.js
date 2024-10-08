const assert = require('assert');

const setSettingsPath = '/test-helpers-http-client-mock/set-settings';

exports.command = function testHelpersHttpMockSetSettings(
  settings = {},
  callback = undefined,
) {
  const urlParams = new URLSearchParams();
  Object.entries(settings).forEach(([key, value]) => {
    urlParams.append(key, value);
  });
  const requestPath = `${setSettingsPath}?${urlParams.toString()}`;
  this.thDrupalFetchURL(requestPath, (result) => {
    assert.equal(JSON.parse(result.value.data).status, 'success');
  }).perform(() => {
    if (typeof callback === 'function') {
      const self = this;
      callback.call(self);
    }
  });

  return this;
};
