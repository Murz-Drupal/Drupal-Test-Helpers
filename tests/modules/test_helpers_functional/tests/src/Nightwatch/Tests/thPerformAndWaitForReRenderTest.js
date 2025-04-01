const getBeforeAfterFunctions = require('../Lib/getBeforeAfterFunctions');

module.exports = {
  ...getBeforeAfterFunctions(),

  'Test thPerformAndWaitForReRender command': (browser) => {
    let callbackExecutes = 0;
    browser
      .drupalRelativeURL('/test-helpers-functional-test/rerender-test-page')
      .thPerformAndWaitForReRender((browser) => {
        browser.click('#refresh-button');
      }, '.test-content')
      .thPerformAndWaitForReRender(
        (browser) => {
          browser.click('#refresh-button');
        },
        '.test-content',
        1000,
        100,
        (result) => {
          browser.assert.equal(result.status, 0);
          browser.assert.equal(result.value, true);
          callbackExecutes += 1;
        },
      )
      .thPerformAndWaitForReRender(
        (browser) => {
          browser.click('#refresh-button');
        },
        '.test-content2',
        100,
        10,
        (result) => {
          browser.assert.equal(result.status, -1);
          browser.assert.equal(
            result.error,
            'The element ".test-content2" was not rerendered.',
          );
          callbackExecutes += 1;
        },
      )
      .perform(() => {
        browser.assert.equal(callbackExecutes, 2);
      });
  },
};
