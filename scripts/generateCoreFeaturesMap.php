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

// @codingStandardsIgnoreLine
const ONELINER = '
export DRUPAL_VERSION=9.3; rm -rf ./drupal_$DRUPAL_VERSION && composer create-project drupal/recommended-project:~$DRUPAL_VERSION.0 drupal_$DRUPAL_VERSION && cd drupal_$DRUPAL_VERSION && composer require drush/drush && composer require drupal/test_helpers:@alpha --prefer-source && ./vendor/bin/drush si --db-url=sqlite://db.sqlite -y
./web/modules/contrib/test_helpers/scripts/generateCoreFeaturesMap.php
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

$container = \Drupal::getContainer();
$drupalVersionArray = explode('.', \Drupal::VERSION);
$drupalVersionMinor = $drupalVersionArray[0] . '.' . $drupalVersionArray[1];

$filename = dirname(__DIR__) . '/src/includes/CoreFeaturesMaps/CoreFeaturesMap.' . $drupalVersionMinor . '.php';

// Generating services map.
$contents .= <<<EOT

const TEST_HELPERS_DRUPAL_CORE_SERVICE_MAP = [

EOT;
foreach ($container->getServiceIds() as $serviceId) {
  $service = $container->get($serviceId);
  if (!is_object($service)) {
    continue;
  }
  $class = get_class($service);
  $info = TestHelpers::getServiceInfoFromClass($class);
  if (isset($info['arguments'])) {
    $arguments = ", 'arguments' => ['" . implode("', '", $info['arguments'] ?? []) . "']";
  }
  else {
    $arguments = '';
  }
  $contents .= "  '$serviceId' => ['class' => '\\$class'$arguments],\n";
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
