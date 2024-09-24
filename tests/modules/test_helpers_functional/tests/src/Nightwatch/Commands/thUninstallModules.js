const endpoint = '/test-helpers-functional/module-installer';

/**
 * Uninstalls one or several modules at once.
 *
 * @param {array} modules
 *   The module machine name to enable.
 * @param {function} callback
 *   A callback which will be called, when creating the role is finished.
 * @return {object}
 *   The thUninstallModules command.
 */
exports.command = function thUninstallModules(modules, callback) {
  const self = this;
  const url = `${endpoint}?uninstall=${modules.join(',')}`;
  this.drupalRelativeURL(url)
    .waitForElementVisible('body')
    .assert.textContains('body', '"status":"success"');

  this.perform(() => {
    if (typeof callback === 'function') {
      callback.call(self);
    }
  });

  return this;
};
