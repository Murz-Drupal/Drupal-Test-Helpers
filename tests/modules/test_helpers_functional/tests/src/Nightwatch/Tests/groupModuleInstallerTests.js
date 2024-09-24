const testInstallModules = ['options', 'datetime'];
const testInstallModulesDependencies = ['text'];

module.exports = {
  // @covers tests/src/Nightwatch/Commands/thInstallModules.js:thInstallModules
  'Test thInstallModules': (browser) => {
    /* eslint-disable prefer-const */
    let modules = [];
    browser
      .thInstallModules(testInstallModules, true)
      .thLogin('admin')
      .drupalRelativeURL('/admin/modules')
      .waitForElementVisible('body')
      .elements('css selector', '.system-modules input:checked', (result) => {
        result.value.forEach((element) => {
          const elementId = element[Object.keys(element)[0]];
          browser.elementIdAttribute(elementId, 'name', (attributeResult) => {
            const parts = attributeResult.value.match(
              /modules\[([^\]]+)\]\[([^\]]+)\]/,
            );
            modules.push(parts[1]);
          });
        });
      })
      .perform(() => {
        const expectedModules = [
          ...testInstallModules,
          ...testInstallModulesDependencies,
        ];
        browser.assert.ok(
          expectedModules.every((element) => modules.includes(element)),
        );
      });
  },

  'Test thUninstallModules': (browser) => {
    /* eslint-disable prefer-const */
    let modules = [];
    browser
      .thUninstallModules(testInstallModules)
      .thLogin('admin')
      .drupalRelativeURL('/admin/modules')
      .waitForElementVisible('body')
      .elements('css selector', '.system-modules input:checked', (result) => {
        result.value.forEach((element) => {
          const elementId = element[Object.keys(element)[0]];
          browser.elementIdAttribute(elementId, 'name', (attributeResult) => {
            const parts = attributeResult.value.match(
              /modules\[([^\]]+)\]\[([^\]]+)\]/,
            );
            modules.push(parts[1]);
          });
        });
      })
      .perform(() => {
        browser.assert.ok(
          testInstallModules.every((element) => !modules.includes(element)),
          testInstallModulesDependencies.every((element) =>
            modules.includes(element),
          ),
        );
      });
  },
};
