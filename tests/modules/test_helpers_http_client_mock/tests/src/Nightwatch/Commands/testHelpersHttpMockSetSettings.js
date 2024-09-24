const setSettingsPath = '/test-helpers-http-client-mock/set-settings';

exports.command = function testHelpersHttpMockSetSettings(
  settings = {},
  callback = undefined,
) {
  const self = this;
  const urlParams = new URLSearchParams();
  Object.entries(settings).forEach(([key, value]) => {
    urlParams.append(key, value);
  });
  const requestPath = `${setSettingsPath}?${urlParams.toString()}`;
  this.drupalRelativeURL(requestPath);

  this.perform(() => {
    if (typeof callback === 'function') {
      callback.call(self);
    }
  });

  return this;
};
