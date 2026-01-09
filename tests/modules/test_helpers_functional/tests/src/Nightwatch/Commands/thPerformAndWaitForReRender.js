/**
 * @file
 * Nightwatch command that executes an action and checks for an element to be re-rendered on the page.
 */

module.exports = class ThPerformAndWaitForReRender {
  /**
   * Executes an action and checks for an element to be re-rendered on the page.
   *
   * Useful to check the action elements on the frontend, which rerender the
   * component without reloading the page.
   *
   * @param {function} action
   *   The action to perform.
   * @param {object} checkElementSelector
   *   The selector to check the element for disappear and appear.
   * @param {number} wait
   *  The time to wait for the element to be re-rendered.
   * @param {number} pause
   *  The time to wait between each check.
   * @param {function} callback
   *  The callback function to execute after the action is performed.
   */
  command(
    action,
    checkElementSelector,
    wait = 5000,
    pause = 100,
    callback = undefined,
  ) {
    let elId;
    let isElReloaded = false;
    this.api
      .findElement(checkElementSelector, (result) => {
        elId = result.value.getId();
      })
      .perform(action(this.api))
      .perform(async () => {
        const steps = Math.floor(wait / pause);
        for (let i = 0; i < steps; i++) {
          // eslint-disable-next-line no-await-in-loop
          await this.api.waitForElementVisible(checkElementSelector);
          // eslint-disable-next-line no-await-in-loop
          const elReloaded = await this.api.findElement(checkElementSelector);
          if (elReloaded.getId() !== elId) {
            isElReloaded = true;
            break;
          }
        }
        if (!isElReloaded) {
          const error = `The element "${checkElementSelector}" was not rerendered.`;
          if (typeof callback === 'function') {
            const self = this;
            callback.call(self, { status: -1, error });
            return false;
          }
          throw new Error(error);
        }
        this.api.perform(() => {
          if (typeof callback === 'function') {
            const self = this;
            callback.call(self, { status: 0, value: true });
          }
        });

        return true;
      });
  }
};
