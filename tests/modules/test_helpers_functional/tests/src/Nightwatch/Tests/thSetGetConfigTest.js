const getBeforeAfterFunctions = require('../Lib/getBeforeAfterFunctions');

module.exports = {
  ...getBeforeAfterFunctions(),

  // @covers tests/src/Nightwatch/Commands/thGetConfig.js:thGetConfig.command
  // @covers tests/src/Nightwatch/Commands/thSetConfig.js:thSetConfig.command
  'Test thGetConfig and thSetConfig in the sync mode': (browser) => {
    let config;
    browser.thGetConfig('system.site', (result) => {
      config = result.value;
      Promise.resolve(
        browser.thSetConfig('system.site', { slogan: 'Foo bar' }),
      );
      browser.thGetConfig('system.site').then((result) => {
        browser.assert.equal(result.slogan, 'Foo bar');
        browser.assert.equal(result.name, config.name);
        browser.thSetConfig('system.site', config, (result) => {
          browser.assert.equal(result.value.slogan, config.slogan);
          browser.assert.equal(result.value.name, config.name);
        });
      });
    });
  },

  // @covers tests/src/Nightwatch/Commands/thGetConfig.js:thGetConfig.command
  // @covers tests/src/Nightwatch/Commands/thSetConfig.js:thSetConfig.command
  'Test thGetConfig and thSetConfig in the async mode': async (browser) => {
    const config = await browser.thGetConfig('system.site');
    await browser.thSetConfig('system.site', { slogan: 'Foo bar' });

    const result1 = await browser.thGetConfig('system.site');
    await browser.assert.equal(result1.slogan, 'Foo bar');
    await browser.assert.equal(result1.name, config.name);

    const result2 = await browser.thSetConfig('system.site', config);
    await browser.assert.equal(result2.slogan, config.slogan);
    await browser.assert.equal(result2.name, config.name);
  },
};
