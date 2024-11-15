const path = require('path');

const assetsDirectory = `${__dirname}${path.sep}assets`;
const urlParams = new URLSearchParams();
const fetchUrl1 = '/test-helpers-test/json-response-1';
const fetchUrl2 = '/test-helpers-test/json-response-2';
urlParams.append('path[]', fetchUrl1);
urlParams.append('path[]', fetchUrl2);
urlParams.append('path[]', fetchUrl2);
urlParams.append('path[]', fetchUrl1);
const url = `/test-helpers-test/http-call-render?${urlParams.toString()}`;
const responseContentTagSelector = '.http-call-render-response';
const expectedResponse1 = '{"title":"foo1"}';
const expectedResponse2 = '{"title":"foo2","bar":"baz"}';
const expectedResponse2Mocked = '{"title":"foo3","bar":"baz"}';

let testedResponsesCount = 0;

module.exports = {
  '@tags': ['test_helpers', 'test_helpers_http_client_mock'],
  before(browser) {
    browser.drupalInstall({
      installProfile: 'test_helpers_http_client_mock_profile',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },

  /**
   * Tests the commands in sync mode.
   *
   * @param {object} browser
   *   The browser object.
   *
   * @covers thGetRequestResponseFromPage()
   * @covers thGetRequestHashesFromPage()
   * @covers thGetRequestResponseByHash()
   * @covers thGetRequestMetadataByHash()
   * @covers thSetRequestResponseByHash()
   */
  'Tests the commands in sync mode': (browser) => {
    let hashes;
    browser
      .testHelpersHttpMockSetSettings({
        mode: 'store',
        directory: assetsDirectory,
      })
      .drupalRelativeURL(url)
      .waitForElementVisible(responseContentTagSelector)
      // Test thGetRequestHashesFromPage().
      .thGetRequestHashesFromPage((response) => {
        hashes = response.value;
        const currentResponsesCount = 4;
        browser.assert.equal(hashes.length, currentResponsesCount);
        testedResponsesCount = currentResponsesCount;
        browser.assert.equal(hashes[0], hashes[3]);
        browser.assert.equal(hashes[1], hashes[2]);
      })
      // Test thGetRequestResponseFromPage().
      .perform(() => {
        browser
          .thGetRequestResponseFromPage((response) => {
            browser.assert.equal(response.value.body, expectedResponse1);
          })
          .thGetRequestResponseFromPage(0, (response) => {
            browser.assert.equal(response.value.body, expectedResponse1);
          })
          .thGetRequestResponseFromPage(1, (response) => {
            browser.assert.equal(response.value.body, expectedResponse2);
          })
          .thGetRequestResponseFromPage(2, (response) => {
            browser.assert.equal(response.value.body, expectedResponse2);
          })
          .thGetRequestResponseFromPage(3, (response) => {
            browser.assert.equal(response.value.body, expectedResponse1);
          });
      })
      // Test thGetRequestResponseByHash() and thSetRequestResponseByHash().
      .perform(() => {
        browser
          .thGetRequestResponseByHash(hashes[1], (response) => {
            browser.assert.equal(response.value, expectedResponse2);
          })
          .thGetRequestResponseByHash(hashes[3], (response) => {
            browser.assert.equal(response.value, expectedResponse1);
          })
          .thSetRequestResponseByHash(hashes[3], expectedResponse2Mocked)
          .thGetRequestResponseByHash(hashes[0], (response) => {
            browser.assert.equal(response.value, expectedResponse2Mocked);
          })
          .thSetRequestResponseByHash(hashes[0], expectedResponse2)
          .thGetRequestResponseByHash(hashes[3], (response) => {
            browser.assert.equal(response.value, expectedResponse2);
          });
      })
      // Test thGetRequestMetadataByHash()
      .perform(() => {
        browser.thGetRequestMetadataByHash(hashes[1], (response) => {
          browser.assert.ok(
            response.value.request.uri.includes(fetchUrl2),
            expectedResponse2Mocked,
          );
        });
      })

      // Test thGetLastRequestsHashes()
      .perform(() => {
        browser.thGetLastRequestsHashes((response) => {
          const lastHashes = response.value;
          const currentResponsesCount = 6;
          browser.assert.equal(lastHashes.length, currentResponsesCount);
          testedResponsesCount = currentResponsesCount;
          browser.assert.equal(lastHashes[0], lastHashes[5]);
          browser.assert.equal(lastHashes[3], lastHashes[4]);
        });
      });
  },

  /**
   * Tests the commands in async mode.
   *
   * @param {object} browser
   *   The browser object.
   *
   * @covers thGetRequestResponseFromPage()
   * @covers thGetRequestHashesFromPage()
   * @covers thGetRequestResponseByHash()
   * @covers thGetRequestMetadataByHash()
   * @covers thSetRequestResponseByHash()
   * @covers thGetLastResponse()
   * @covers thDeleteRequestResponseByHash()
   */

  'Shortened test in async mode': async (browser) => {
    await browser
      .testHelpersHttpMockSetSettings({
        mode: 'store',
        directory: assetsDirectory,
      })
      .drupalRelativeURL(url)
      .waitForElementVisible(responseContentTagSelector);

    let response;

    response = await browser.thGetRequestResponseFromPage();
    browser.assert.equal(response.body, expectedResponse1);

    response = await browser.thGetRequestResponseFromPage(2);
    browser.assert.equal(response.body, expectedResponse2);

    const hashes = await browser.thGetRequestHashesFromPage();
    browser.assert.equal(hashes.length, 4);
    browser.assert.equal(hashes[0], hashes[3]);
    browser.assert.equal(hashes[1], hashes[2]);

    const hashesLast = await browser.thGetLastRequestsHashes();
    const currentResponsesCount = 4;
    browser.assert.equal(
      hashesLast.length,
      testedResponsesCount + currentResponsesCount,
    );
    browser.assert.equal(hashesLast[0], hashesLast[9]);
    browser.assert.equal(hashesLast[1], hashesLast[8]);

    response = await browser.thGetRequestResponseByHash(hashes[1]);
    browser.assert.equal(response, expectedResponse2);
    await browser.thSetRequestResponseByHash(
      hashes[1],
      expectedResponse2Mocked,
    );
    response = await browser.thGetRequestResponseByHash(hashes[1]);
    browser.assert.equal(response, expectedResponse2Mocked);
    await browser.thSetRequestResponseByHash(hashes[1], expectedResponse2);
    response = await browser.thGetRequestResponseByHash(hashes[1]);
    browser.assert.equal(response, expectedResponse2);

    response = await browser.thGetRequestMetadataByHash(hashes[3]);
    browser.assert.ok(
      response.request.uri.includes(fetchUrl1),
      expectedResponse1,
    );

    const hashesLast2 = await browser.thGetLastRequestsHashes();
    browser.assert.equal(hashesLast2.length, 12);
    browser.assert.equal(hashesLast2[2], hashesLast2[11]);
    browser.assert.equal(hashesLast2[1], hashesLast2[10]);

    const hashLast0 = await browser.thGetLastResponse();
    browser.assert.equal(hashLast0.body, expectedResponse1);
    const hashLast1 = await browser.thGetLastResponse(1);
    browser.assert.equal(hashLast1.body, expectedResponse2);

    // Cleanup asset files.
    const hashesUnique = [...new Set(hashesLast2)];
    // We need to use the for loop here, because the `await` inside the
    // forEach loop doesn't work.
    // eslint-disable-next-line no-restricted-syntax
    for (const hash of hashesUnique) {
      browser.thDeleteRequestResponseByHash(hash);
    }
  },
};
