const path = require('path');
const fs = require('fs');

module.exports = {
  // @covers tests/src/Nightwatch/Commands/thGetDrupalComponentPath.js:thGetDrupalComponentPath
  'Test finding of path to a Drupal component': async (browser) => {
    const drupalRootCheckFile = 'core/lib/Drupal.php';
    const drupalRoot = await browser.thGetDrupalComponentPath('root');
    browser.assert.ok(
      fs.existsSync(drupalRoot + path.sep + drupalRootCheckFile),
    );
    browser.assert.equal(
      `${drupalRoot + path.sep}core`,
      await browser.thGetDrupalComponentPath('core'),
    );
    browser.assert.equal(
      `${drupalRoot + path.sep}core/modules/comment`,
      await browser.thGetDrupalComponentPath('comment'),
    );
    console.log(__dirname);
    const testHelpersPath = path.resolve(
      __dirname,
      Array(7).fill('..').join(path.sep),
    );
    const testHelpersFunctionalPath = path.resolve(
      __dirname,
      Array(4).fill('..').join(path.sep),
    );
    browser.assert.equal(
      testHelpersPath,
      await browser.thGetDrupalComponentPath('test_helpers'),
    );
    browser.assert.equal(
      testHelpersFunctionalPath,
      await browser.thGetDrupalComponentPath('test_helpers_functional'),
    );
  },
};
