const getBeforeAfterFunctions = require('../Lib/getBeforeAfterFunctions');

module.exports = {
  ...getBeforeAfterFunctions(true),

  // @covers tests/src/Nightwatch/Commands/thCacheClear.js::ThCacheClear.command
  'Test thCacheClear': async (browser) => {
    browser
      .thInstallModules(['page_cache'])
      .thDrupalFetchURL('/', (result) => {
        browser.assert.equal(result.value.headers['x-drupal-cache'], 'MISS');
      })
      .thDrupalFetchURL('/', (result) => {
        browser.assert.equal(result.value.headers['x-drupal-cache'], 'HIT');
      })
      .thDrupalFetchURL('/test-helpers-functional/cache-clear')
      .thDrupalFetchURL('/', (result) => {
        browser.assert.equal(result.value.headers['x-drupal-cache'], 'MISS');
      })
      .thDrupalFetchURL(
        '/test-helpers-functional/cache-clear?tags=config:user.role.authenticated',
      )
      .thDrupalFetchURL('/', (result) => {
        browser.assert.equal(result.value.headers['x-drupal-cache'], 'HIT');
      })
      .thDrupalFetchURL(
        '/test-helpers-functional/cache-clear?tags=config:user.role.anonymous',
      )
      .thDrupalFetchURL('/', (result) => {
        browser.assert.equal(result.value.headers['x-drupal-cache'], 'MISS');
      })
      .thDrupalFetchURL('/test-helpers-functional/cache-clear?bins=bootstrap')
      .thDrupalFetchURL('/', (result) => {
        browser.assert.equal(result.value.headers['x-drupal-cache'], 'HIT');
      })
      .thDrupalFetchURL('/test-helpers-functional/cache-clear?bins=page')
      .thDrupalFetchURL('/', (result) => {
        browser.assert.equal(result.value.headers['x-drupal-cache'], 'MISS');
      })
      .thUninstallModules(['page_cache']);
  },
};
