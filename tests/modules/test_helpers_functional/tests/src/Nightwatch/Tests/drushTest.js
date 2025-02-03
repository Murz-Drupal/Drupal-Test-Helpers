const { execSync } = require("child_process");

module.exports = {
  '@tags': ['test_helpers'],
  before: function (browser) {
    browser
      .drupalInstall();
  },
  after: function (browser) {
    browser
      .drupalUninstall();
  },
  'Run drush directly': (browser) => {
    browser
      .drupalRelativeURL('/')
      .perform(async () => {
        const drushCommand = `drush status`;
        console.log(drushCommand);
        const result = execSync(drushCommand);
        console.log(result.toString());
      });

  },
  'Run drush with ENV': (browser) => {
    browser
      .drupalRelativeURL('/')
      .perform(async () => {
        const simpletestUserAgentCookieObject = await browser.cookies.get(
          'SIMPLETEST_USER_AGENT',
        );
        const simpletestUserAgentCookie = decodeURIComponent(
          simpletestUserAgentCookieObject.value,
        );
        const drushCommand = `HTTP_USER_AGENT="${simpletestUserAgentCookie}" drush status`;
        console.log(drushCommand);
        const result = execSync(drushCommand);
        console.log(result.toString());
      });
  },

};
