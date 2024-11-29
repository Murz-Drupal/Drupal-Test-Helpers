/**
 * @file
 * Defines the ThGetDrupalComponentPath Nightwatch command.
 */

const fs = require('fs');
const path = require('path');

module.exports = class ThGetDrupalComponentPath {
  /**
   * Provides absolute directory path to a Drupal module, core or application root.
   *
   * @param {string} component
   *   Any module machine name, 'root' or 'core'.
   * @param {function} [callback=undefined]
   *   A callback function which will be called when the environment variable
   *   value is retrieved.
   *
   * @return {string}
   *   An absolute directory path to a Drupal component.
   */
  command(component, callback = undefined) {
    const rootCheckPath = 'core/core.services.yml';
    let componentPath;

    const findParentDirWithPath = (startDir, checkPath) => {
      const dirs = startDir.split(path.sep);
      let targetDir;
      do {
        const scanDir = dirs.join(path.sep);
        if (fs.existsSync(scanDir + path.sep + checkPath)) {
          targetDir = scanDir;
        } else {
          dirs.pop();
        }
        if (dirs.length === 0) {
          throw new Error("Can't find the target directory.");
        }
      } while (targetDir === undefined);
      return targetDir;
    };

    function findChildDirWithFile(startDir, fileName) {
      let foundFilePath;
      const exclusions = ['node_modules/', 'vendor/', 'sites/', 'themes/'];
      const files = fs.readdirSync(startDir);
      files.find((file) => {
        if (exclusions.some((exclude) => startDir.includes(exclude))) {
          return false;
        }
        const filePath = path.join(startDir, file);
        if (file === fileName) {
          componentPath = filePath;
          return true;
        }
        try {
          const fileStat = fs.statSync(filePath);
          if (fileStat.isDirectory()) {
            return findChildDirWithFile(filePath, fileName);
          }
        } catch (error) {
          return false;
        }
        return false;
      });

      return foundFilePath || false;
    }

    let drupalRoot;
    if (process.env.CI_PROJECT_DIR && process.env._WEB_ROOT) {
      const drupalRootToCheck =
        process.env.CI_PROJECT_DIR + path.sep + process.env._WEB_ROOT;
      if (fs.existsSync(drupalRootToCheck + path.sep + rootCheckPath)) {
        drupalRoot = drupalRootToCheck;
      }
    }
    if (drupalRoot === undefined) {
      drupalRoot = findParentDirWithPath(__dirname, rootCheckPath);
    }

    let componentDir;
    switch (component) {
      case 'root':
        componentDir = drupalRoot;
        break;

      case 'core':
        componentDir = `${drupalRoot}${path.sep}core`;
        break;

      default:
        findChildDirWithFile(drupalRoot, `${component}.info.yml`);
        componentDir = path.dirname(componentPath);
    }

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, {
        status: 0,
        value: componentDir,
      });
    }

    return componentDir;
  }
};
