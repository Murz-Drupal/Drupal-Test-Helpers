#!/usr/bin/env php
<?php

/**
 * @file
 * Generates list of services and entities from the current Drupal Core.
 *
 * Requires a clean installation of Drupal, without any contrib modules.
 */

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

// Works only for Core >= 8.9
// For 8.0 - use drupal/drupal instead of drupal/recommended-project and
// drush/drush:^8.
// For versions less than 9.3 - use PHP < 8.1.
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

const CORE_DIRECTORY = 'core';
const CORE_SERVICES_FILE = CORE_DIRECTORY . '/core.services.yml';
const DRUPAL_BOOTSTRAP_FILE = CORE_DIRECTORY . '/includes/bootstrap.inc';

if (!file_exists(DRUPAL_BOOTSTRAP_FILE)) {
  throw new \Exception("Run this script only from the Drupal Root directory. Drupal bootstrap file not found: " . DRUPAL_BOOTSTRAP_FILE);
}

// Disable PHPStan error:
// Path in include_once() "autoload.php" is not a file or it does not exist.
// because the file will be present in the runtime.
// @phpstan-ignore-next-line
$autoloader = include_once 'autoload.php';

// Disable PHPStan error:
// Path in require_once() "core/includes/bootstrap.inc" is not a file or it does
// not exist because the file will be present in the runtime.
// @phpstan-ignore-next-line
require_once DRUPAL_BOOTSTRAP_FILE;

$request = Request::createFromGlobals();
Settings::initialize(dirname(__DIR__, 2), DrupalKernel::findSitePath($request), $autoloader);
DrupalKernel::createFromRequest($request, $autoloader, 'prod')->boot();

\Drupal::service('request_stack')->push($request);

$drupalVersionArray = explode('.', str_replace('-dev', '', \Drupal::VERSION));
$drupalVersionMinor = $drupalVersionArray[0] . '.' . $drupalVersionArray[1];

$filename = dirname(__DIR__) . '/src/lib/CoreFeaturesMaps/CoreFeaturesMap.' . $drupalVersionMinor . '.php';

// Generating services map.
$contents .= <<<EOT

const TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP = [

EOT;

$files = [];
$it = new RecursiveDirectoryIterator(CORE_DIRECTORY);
foreach (new RecursiveIteratorIterator($it) as $file) {
  if (strpos($file, '/tests/')) {
    continue;
  }
  if (preg_match('#\.services.yml$#', $file)) {
    $files[] = $file->getPathName();
  }
}

$servicesAdded = [];
foreach ($files as $file) {
  $data = Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS);
  foreach (array_keys($data['services'] ?? []) as $service) {
    // Prevent adding duplicated services.
    // This is a case for the 'pgsql.entity.query.sql' service at least,
    // that is present in two files:
    // - core/core.services.yml
    // - core/modules/pgsql/pgsql.services.yml
    // We need to add it only once.
    if (in_array($service, $servicesAdded)) {
      continue;
    }
    $servicesAdded[] = $service;
    if (
      strpos($service, '\\') !== FALSE
      || $service == '_defaults'
    ) {
      continue;
    }
    $contents .= <<<EOT
  '$service' => '$file',

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

// Generating default parameters.
$data = Yaml::parseFile('core/core.services.yml', Yaml::PARSE_CUSTOM_TAGS);
$parametersJson = json_encode($data['parameters']);
// We should encode double backslashes twice to make JSON string valid.
$parametersJson = str_replace('\\', '\\\\', $parametersJson);
$contents .= <<<EOT

const TEST_HELPERS_DRUPAL_CORE_PARAMETERS = '$parametersJson';

EOT;

if (!file_put_contents($filename, $contents)) {
  throw new \Exception("Error creating file $filename");
}

echo "Generated services file: $filename" . PHP_EOL;
