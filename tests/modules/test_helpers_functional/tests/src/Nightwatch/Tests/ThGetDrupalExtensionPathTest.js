const path = require('path');
const fs = require('fs');

module.exports = {
  // @covers tests/src/Nightwatch/Commands/thGetDrupalExtensionPath.js:thGetDrupalExtensionPath
  'Test finding of path to a Drupal extension': async (browser) => {
    const drupalRootCheckFile = 'core/lib/Drupal.php';
    const drupalRoot = await browser.thGetDrupalExtensionPath('root');
    browser.assert.ok(
      fs.existsSync(drupalRoot + path.sep + drupalRootCheckFile),
    );
    browser.assert.equal(
      `${drupalRoot + path.sep}core`,
      await browser.thGetDrupalExtensionPath('core'),
    );
    browser.assert.equal(
      `${drupalRoot + path.sep}core/modules/comment`,
      await browser.thGetDrupalExtensionPath('comment'),
    );
    const testHelpersPath = path.resolve(
      __dirname,
      Array(7).fill('..').join(path.sep),
    );
    browser.assert.equal(
      testHelpersPath,
      await browser.thGetDrupalExtensionPath('test_helpers'),
    );
    const testHelpersFunctionalPath = path.resolve(
      __dirname,
      Array(4).fill('..').join(path.sep),
    );
    browser.assert.equal(
      testHelpersFunctionalPath,
      await browser.thGetDrupalExtensionPath('test_helpers_functional'),
    );
  },
};
