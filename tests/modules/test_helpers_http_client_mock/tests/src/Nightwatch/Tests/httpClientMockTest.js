const path = require('path');
const fs = require('fs');
const { EventEmitter } = require('events');

const requestPath1 =
  '/test-helpers-test/http-call-render?path=/test-helpers-test/json-response-1';
const requestPath2 =
  '/test-helpers-test/http-call-render?path=/test-helpers-test/json-response-2';

const metatagName = 'TestHelpersHttpClientMockRequestsHashes';

const responseContentTagSelector = '.http-call-render-response';

// We should use a module subdirectory, because the drupal.org pipeline creates
// symlinks for all the root module directories, so when we create a new
// directory - it is not symlinked and the files become not found.
// @see https://git.drupalcode.org/project/gitlab_templates/-/blob/a3ffedee6a416be6b461904449a2aad5888fb206/includes/include.drupalci.main.yml#L216
const assetsDirectory = `${__dirname}${path.sep}assets`;

const readStoredResponse = (hash) => {
  const mockedResponseFile = `${assetsDirectory}${path.sep}${hash}.json`;
  const content = fs.readFileSync(mockedResponseFile).toString();
  return content;
};

const writeStoredResponse = (hash, content, metadata = null) => {
  const mockedResponseFile = `${assetsDirectory}${path.sep}${hash}.json`;
  const mockedResponseMetadataFile = `${assetsDirectory}${path.sep}${hash}_metadata.json`;
  if (!metadata) {
    metadata = {
      tests: [],
      request: {
        method: 'get',
        uri: 'http://localhost/test-helpers-test/json-response-1',
      },
      response: {
        status: 200,
        headers: [],
      },
    };
  }
  fs.mkdirSync(assetsDirectory, { recursive: true });
  fs.writeFileSync(mockedResponseFile, content);
  fs.writeFileSync(mockedResponseMetadataFile, JSON.stringify(metadata));
};

const deleteStoredResponse = (hash) => {
  const mockedResponseFile = `${assetsDirectory}${path.sep}${hash}.json`;
  if (fs.existsSync(mockedResponseFile)) {
    fs.unlinkSync(mockedResponseFile);
  }
  const mockedResponseMetadataFile = `${assetsDirectory}${path.sep}${hash}_metadata.json`;
  if (fs.existsSync(mockedResponseMetadataFile)) {
    fs.unlinkSync(mockedResponseMetadataFile);
  }
};

let request1Hash;
let request2Hash;
let request1MockHash;

module.exports = {
  '@tags': ['test_helpers', 'test_helpers_http_client_mock'],
  beforeEach() {
    // Increase max listeners for this long running test - a workaround for the
    // issue https://github.com/nightwatchjs/nightwatch/issues/408
    EventEmitter.defaultMaxListeners = 100;
  },
  afterEach() {
    // Reset max listeners to the node.js default once the test is complete.
    EventEmitter.defaultMaxListeners = 10;
  },
  before(browser) {
    browser.drupalInstall({
      installProfile: 'test_helpers_http_client_mock_profile',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test the store mode': (browser) => {
    browser
      .testHelpersHttpMockSetSettings({
        mode: 'store',
        directory: assetsDirectory,
      })
      .drupalRelativeURL(requestPath1)
      .waitForElementVisible(responseContentTagSelector)
      .getAttribute(`meta[name="${metatagName}"]`, 'content', (result) => {
        [request1Hash] = JSON.parse(result.value);
        request1MockHash = request1Hash;
        const apiResponse1Data = JSON.parse(readStoredResponse(request1Hash));
        browser.assert.textContains('body', apiResponse1Data.title);
      })
      .drupalRelativeURL(requestPath2)
      .waitForElementVisible(responseContentTagSelector)
      .getAttribute(`meta[name="${metatagName}"]`, 'content', (result) => {
        [request2Hash] = JSON.parse(result.value);
        const apiResponse2Data = JSON.parse(readStoredResponse(request2Hash));
        browser.assert.textContains(
          responseContentTagSelector,
          apiResponse2Data.title,
        );
      })
      .perform(() => {
        deleteStoredResponse(request1Hash);
        deleteStoredResponse(request2Hash);
      });
  },

  'Test the mock mode': (browser) => {
    const mockedResponse = {
      title: 'Mocked title',
    };
    browser
      .testHelpersHttpMockSetSettings({
        mode: 'mock',
        directory: assetsDirectory,
      })
      .perform(() => {
        writeStoredResponse(request1MockHash, JSON.stringify(mockedResponse));
      })
      .drupalRelativeURL(requestPath1)
      .waitForElementVisible(responseContentTagSelector)
      .assert.textContains(responseContentTagSelector, mockedResponse.title)
      .perform(() => {
        deleteStoredResponse(request1MockHash);
      });
  },
};
