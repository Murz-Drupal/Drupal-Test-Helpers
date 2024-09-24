module.exports = {
  '@tags': ['test_helpers', 'test_helpers_functional'],
  before(browser) {
    browser.drupalInstall();
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test installing the test_helpers_functional module': (browser) => {
    browser
      .drupalInstallModule('test_helpers_functional')
      .thLogin('admin')
      .drupalRelativeURL('/user')
      .assert.textEquals('h1', 'admin');
  },
};
