/** A number to use in tests to randomize values. */
const seedNumber = 1000 + Math.floor(Math.random() * 8000);

module.exports = {
  // @covers tests/src/Nightwatch/Commands/thCreateUser.js
  'Test thCreateUser with a user by just name and log in': (browser) => {
    const name = `test_user_${seedNumber + 1}`;
    browser
      .thCreateUser({ name }, true)
      .drupalRelativeURL('/user')
      .assert.textContains('h1', name);
  },

  // @covers tests/src/Nightwatch/Commands/thCreateUser.js
  // @covers tests/src/Nightwatch/Commands/thLogin.js
  'Test thCreateUser with a user by just name and thLogin': (browser) => {
    const name = `test_user_${seedNumber + 2}`;
    browser
      .thCreateUser({ name })
      .thLogin(name)
      .drupalRelativeURL('/user')
      .assert.textContains('h1', name);
  },

  // @covers tests/src/Nightwatch/Commands/thCreateUser.js:thCreateUser
  'Test thCreateUser with a user by name and password': (browser) => {
    const name = `test_user_${seedNumber + 3}`;
    const password = `pass_${seedNumber + 3}`;
    browser
      .thCreateUser({ name, password })
      .drupalLogin({ name, password })
      .assert.textContains('h1', name);
  },

  // @covers tests/src/Nightwatch/Commands/thCreateUser.js:thCreateUser
  'Test thCreateUser with permissions': (browser) => {
    const name = `test_user_${seedNumber + 4}`;
    const permissions = ['access administration pages', 'administer users'];
    let userId;
    let customRole;
    let assignedPermissions;
    browser
      .thCreateUser({ name, permissions })
      .thLogin('admin')
      .drupalRelativeURL('/admin/people')
      .useXpath()
      .getAttribute(`//a[.='${name}']`, 'href', (result) => {
        userId = result.value.split('/').pop();
      })
      .useCss()
      .perform(() => {
        browser.drupalRelativeURL(`/user/${userId}/edit`);
      })
      .getAttribute(
        {
          selector: '[data-drupal-selector="edit-roles"] input:checked',
          index: 1,
        },
        'value',
        (result) => {
          customRole = result.value;
        },
      )
      .perform(() => {
        const getPermissions = (result) => {
          result.value.forEach((element) => {
            const elementId = element[Object.keys(element)[0]];
            browser.elementIdAttribute(elementId, 'name', (attributeResult) => {
              const permission = attributeResult.value.match(/\[(.*?)\]/)[1];
              assignedPermissions.push(permission);
            });
          });
        };
        assignedPermissions = [];
        browser
          .drupalRelativeURL(`/admin/people/permissions/${customRole}`)
          .elements(
            'css selector',
            'form.user-admin-permissions input:not(.dummy-checkbox):checked',
            (result) => {
              getPermissions(result);
            },
          );
      })
      .perform(() => {
        browser.assert.deepEqual(assignedPermissions, permissions);
      });
  },
};
