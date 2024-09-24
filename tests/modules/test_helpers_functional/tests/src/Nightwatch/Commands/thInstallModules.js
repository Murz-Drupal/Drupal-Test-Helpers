const endpoint = '/test-helpers-functional/module-installer';

/**
 * Installs one or several modules at once, optionally with dependencies.
 *
 * @param {array} modules
 *   The module machine name to enable.
 * @param {boolean} enableDependencies
 *   Force to install dependencies if applicable.
 * @param {function} callback
 *   A callback which will be called, when creating the role is finished.
 * @return {object}
 *   The thInstallModules command.
 */
exports.command = function thInstallModules(
  modules,
  enableDependencies = false,
  callback = undefined,
) {
  const self = this;
  const url = `${endpoint}?install=${modules.join(',')}&enable_dependencies=${
    enableDependencies ? '1' : '0'
  }`;
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
