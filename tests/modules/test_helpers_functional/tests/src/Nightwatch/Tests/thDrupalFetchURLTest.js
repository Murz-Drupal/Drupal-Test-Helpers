const assert = require('assert');
const getBeforeAfterFunctions = require('../Lib/getBeforeAfterFunctions');

module.exports = {
  ...getBeforeAfterFunctions(),

  // @covers tests/src/Nightwatch/Commands/thDrupalFetchURL.js::thDrupalFetchURL.command
  'Test thDrupalFetchURL': async (browser) => {
    browser
      .thDrupalFetchURL('/', (result) => {
        const response = result.value;
        browser.assert
          .equal(response.status, 200)
          .assert.equal(response.statusText, 'OK')
          .assert.strictEqual(typeof response.headers.connection, 'string')
          .assert.equal(response.config.drupalPath, '/');
      })
      .thDrupalFetchURL('/non-exist-url', (result) => {
        const response = result.value;
        browser.assert.equal(response.status, 404);
      })
      .thDrupalFetchURL('/test-helpers-test/json-response-1', (result) => {
        const response = result.value;
        browser.assert.equal(response.status, 200);
        browser.assert.equal(response.body, '{"title":"foo1"}');
        browser.assert.equal(
          response.headers['content-type'],
          'application/json',
        );
        browser.assert.equal(response.headers['multiple-header'], 'value2');
        browser.assert.deepEqual(response.cookies, [
          {
            name: 'TestCookie1',
            value: 'foo',
            path: '/',
            httponly: true,
            samesite: 'lax',
          },
          {
            name: 'TestCookie2',
            value: 'bar',
            path: '/',
            httponly: true,
            samesite: 'lax',
          },
        ]);
      })
      .thDrupalFetchURL(
        '/user/login?_format=json',
        'POST',
        '{"name":"foo", "pass":"bar"}',
        (result) => {
          browser.assert
            .equal(result.value.status, 400)
            .assert.equal(
              result.value.body,
              '{"message":"Sorry, unrecognized username or password."}',
            );
        },
      );

    // Testing the direct return value.
    let response = await browser.thDrupalFetchURL('/admin');
    await browser.assert.equal(response.status, 403);

    // Testing cookies.
    response = await browser.thDrupalFetchURL(
      '/test-helpers-test/json-response-1',
    );
    assert(response.cookies.some((obj) => obj.name === 'TestCookie2'));
    assert(response.cookies.some((obj) => obj.name === 'TestCookie1'));
    let browserCookies = await browser.getCookies();
    assert(browserCookies.some((obj) => obj.name === 'TestCookie2'));
    assert(browserCookies.some((obj) => obj.name === 'TestCookie1'));

    response = await browser.thDrupalFetchURL(
      '/test-helpers-test/json-response-2',
    );
    assert(
      response.cookies.some(
        (obj) => obj.name === 'TestCookie1' && obj.value === 'deleted',
      ),
    );
    assert(!response.cookies.some((obj) => obj.name === 'TestCookie2'));
    browserCookies = await browser.getCookies();
    assert(browserCookies.some((obj) => obj.name === 'TestCookie2'));
    assert(!browserCookies.some((obj) => obj.name === 'TestCookie1'));
  },
};
