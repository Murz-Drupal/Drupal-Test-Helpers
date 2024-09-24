module.exports = {
  // @covers tests/src/Nightwatch/Commands/thSetEnvs.js:thSetEnvs
  // @covers tests/src/Nightwatch/Commands/thGetEnv.js:thGetEnv
  'Test setEnvs and getEnv': (browser) => {
    browser
      .thSetEnvs({
        MY_ENV_1: 'value1',
        MY_ENV_2: 'value2',
        MY_ENV_3: 'My value "three" with quotes',
      })
      .thGetEnv('MY_ENV_1', (result) => {
        browser.assert.equal(result.value, 'value1');
      })
      .thGetEnv('MY_ENV_2', (result) => {
        browser.assert.equal(result.value, 'value2');
      })
      .thGetEnv('MY_ENV_3', (result) => {
        browser.assert.equal(result.value, 'My value "three" with quotes');
      })
      .thSetEnvs({
        MY_ENV_2: 'value2_overridden',
        MY_ENV_1: '',
      })
      .thGetEnv('MY_ENV_1', (result) => {
        browser.assert.equal(result.value, '');
      })
      .thGetEnv('MY_ENV_2', (result) => {
        browser.assert.equal(result.value, 'value2_overridden');
      })
      .thGetEnv('MY_ENV_3', (result) => {
        browser.assert.equal(result.value, 'My value "three" with quotes');
      });
  },
};
