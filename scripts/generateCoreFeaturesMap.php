#!/usr/bin/env php
<?php

/**
 * @file
 * Generates list of services and entities from the current Drupal Core.
 *
 * Requres a clean installation of Drupal, without any contrib modules.
 */

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

// Works only for Core >= 8.9
// For 8.0 - use drupal/drupal instead of drupal/recommended-project and
// drush/drush:^8.
// For versions less than 9.3 - use PHP < 8.1.
// @codingStandardsIgnoreLine
const ONELINER = '
export DRUPAL_VERSION=9.4
export ISSUE_ID=3388492
export ISSUE_BRANCH=3388492-parent-services
rm -rf ./drupal_$DRUPAL_VERSION && composer create-project drupal/recommended-project:~$DRUPAL_VERSION.0 drupal_$DRUPAL_VERSION && \
cd drupal_$DRUPAL_VERSION && composer require drush/drush && composer require drupal/test_helpers --prefer-source && ./vendor/bin/drush si --db-url=sqlite://db.sqlite -y && \
cd ./web/modules/contrib/test_helpers && \
git remote add core-features git@git.drupal.org:issue/test_helpers-$ISSUE_ID.git && \
git fetch core-features && \
git checkout core-features/$ISSUE_BRANCH && \
git checkout $ISSUE_BRANCH && \
./scripts/generateCoreFeaturesMap.php && \
git commit -a -m "Drupal $DRUPAL_VERSION services" && \
git push && \
cd ../../../../..
';

$contents = <<<EOT
<?php

/**
 * @file
 * Pre-generated list of the services from a clean Drupal installation.
 *
 * This list can be regenerated on a clean Drupal installation using the command
 * line script `scripts/generateCoreFeaturesMap.php`.
 */

// @codingStandardsIgnoreFile

EOT;

require_once __DIR__ . '/../src/TestHelpers.php';
$drupalRoot = TestHelpers::getDrupalRoot();
chdir($drupalRoot);
$autoloader = include_once $drupalRoot . '/autoload.php';

require_once $drupalRoot . '/core/includes/bootstrap.inc';

$request = Request::createFromGlobals();
Settings::initialize(dirname(__DIR__, 2), DrupalKernel::findSitePath($request), $autoloader);
DrupalKernel::createFromRequest($request, $autoloader, 'prod')->boot();

\Drupal::service('request_stack')->push($request);

$drupalVersionArray = explode('.', \Drupal::VERSION);
$drupalVersionMinor = $drupalVersionArray[0] . '.' . $drupalVersionArray[1];

$filename = dirname(__DIR__) . '/src/lib/CoreFeaturesMaps/CoreFeaturesMap.' . $drupalVersionMinor . '.php';

// Generating services map.
$contents .= <<<EOT

const TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP = [

EOT;

$it = new RecursiveDirectoryIterator($drupalRoot . '/core');
foreach (new RecursiveIteratorIterator($it) as $file) {
  if (strpos($file, '/tests/')) {
    continue;
  }
  if (preg_match('#\.services.yml$#', $file)) {
    $files[] = $file->getPathName();
  }
}

foreach ($files as $file) {
  $data = Yaml::parseFile($file);
  $fileRelative = ltrim(str_replace($drupalRoot, '', $file), '/');
  foreach (array_keys($data['services'] ?? []) as $service) {
    if (strpos($service, '\\') !== FALSE) {
      continue;
    }
    $contents .= <<<EOT
  '$service' => '$fileRelative',

EOT;
  }
}

$contents .= <<<EOT
];

EOT;

// Generating storage map.
$contents .= <<<EOT

const TEST_HELPERS_DRUPAL_CORE_STORAGE_MAP = [

EOT;
$entityTypeManager = \Drupal::service('entity_type.manager');
foreach ($entityTypeManager->getDefinitions() as $type => $definition) {
  $class = "'\\" . $definition->getClass() . "'";
  $contents .= "  '$type' => $class,\n";
}
$contents .= <<<EOT
];

EOT;

if (!file_put_contents($filename, $contents)) {
  throw new \Exception("Error creating file $filename");
}

echo "Generated services file: $filename" . PHP_EOL;
