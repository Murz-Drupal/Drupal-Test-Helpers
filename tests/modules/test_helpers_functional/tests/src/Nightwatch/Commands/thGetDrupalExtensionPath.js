/**
 * @file
 * Defines the ThGetDrupalExtensionPath Nightwatch command.
 */

const fs = require('fs');
const path = require('path');

module.exports = class ThGetDrupalExtensionPath {
  /**
   * Provides an absolute path to a Drupal module, core, site directory or application root.
   *
   * @param {string} extension
   *   Any drupal extension name (module, theme, profile), or 'root', 'core', 'site'.
   *   - `root` - Drupal root directory.
   *   - `core` - Drupal Core directory.
   *   - `site` - current testing site, i.e. "[ROOT]sites/simpletest/1234567".
   * @param {function} [callback=undefined]
   *   A callback function which will be called when the environment variable
   *   value is retrieved.
   *
   * @return {string}
   *   An absolute directory path to a Drupal extension.
   */
  command(extension, callback = undefined) {
    const rootCheckPath = 'core/core.services.yml';
    let extensionPath;

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
      const exclusions = [
        `${path.sep}node_modules${path.sep}`,
        `${path.sep}vendor${path.sep}`,
      ];
      const files = fs.readdirSync(startDir);
      files.find((file) => {
        if (exclusions.some((exclude) => startDir.includes(exclude))) {
          return false;
        }
        const filePath = path.join(startDir, file);
        if (file === fileName) {
          extensionPath = filePath;
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

      return extensionPath || false;
    }

    function findExtensionByName(root, extensionName) {
      // Find extension by its Info file.
      const fileName = `${extensionName}.info.yml`;
      // To prevent performance loss, we should employ
      // exclusive search based on the next patterns:
      // - [root]/sites/*/[profiles|modules|themes]
      // - [root]/[profiles|modules|themes]
      // - [root]/[core]/[profiles|modules|themes]
      const extensionTypes = ['modules', 'themes', 'profiles'];
      const extensionPathPatterns = [];
      [`${root}${path.sep}`, `${root}${path.sep}core${path.sep}`].forEach(
        (prefix) => {
          extensionTypes.forEach((type) => {
            if (fs.existsSync(prefix + type)) {
              extensionPathPatterns.push(prefix + type);
            }
          });
        },
      );
      fs.readdirSync(`${root}${path.sep}sites`, { withFileTypes: true })
        .filter((entry) => entry.isDirectory() && entry.name !== 'simpletest')
        .forEach((entry) => {
          extensionTypes.forEach((type) => {
            const pattern = `${root}${path.sep}sites${path.sep}${entry.name}${path.sep}${type}`;
            if (fs.existsSync(pattern)) {
              extensionPathPatterns.unshift(pattern);
            }
          });
        });
      extensionPathPatterns.find((pattern) =>
        findChildDirWithFile(pattern, fileName),
      );
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

    let extensionDir;
    switch (extension) {
      case 'root':
        extensionDir = drupalRoot;
        break;

      case 'core':
        extensionDir = `${drupalRoot}${path.sep}core`;
        break;

      case 'site': {
        // @todo Find a better way to get the siteId.
        const siteId = this.api.globals.drupalDbPrefix.replace(/\D/g, '');
        // Keep in sync with the TestSiteInstallCommand::ensureDirectory().
        extensionDir = `${drupalRoot}${path.sep}sites${path.sep}simpletest${path.sep}${siteId}`;
        break;
      }

      default:
        findExtensionByName(drupalRoot, extension);
        extensionDir = extensionPath ? path.dirname(extensionPath) : false;
    }

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, {
        status: 0,
        value: extensionDir,
      });
    }

    return extensionDir;
  }
};
