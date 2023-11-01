<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler;

/**
 * Tests UrlGeneratorStub class.
 *
 * @coversDefaultClass \Drupal\Core\Routing\UrlGenerator
 * @group test_helpers
 */
class RouteProviderStubTest extends UnitTestCase {

  /**
   * @covers ::stubAddRoute
   * @covers ::stubAddRouteByPath
   */
  public function testGeneral() {
    $service = TestHelpers::service('router.route_provider');
    TestHelpers::assertException(
      fn() => $service->getRouteByName('entity.node.canonical'),
      RouteNotFoundException::class
    );

    $path = '/node/{node}';
    $routeOptions = [
      'parameters' => [
        'node' => [
          'type' => 'entity:node',
          'converter' => 'paramconverter.entity',
        ],
      ],
    ];
    $route = new Route(
      $path,
      [],
      [],
      $routeOptions,
      '',
    );
    $service->stubAddRoute('entity.node.canonical', $route);
    $route = $service->getRouteByName('entity.node.canonical');
    $this->assertEquals($path, $route->getPath());
    $routeOptions += [
      'compiler_class' => RouteCompiler::class,
    ];
    $this->assertEquals($routeOptions, $route->getOptions());

    $path = '/node/{node}/edit';
    $service->stubAddRouteByPath('entity.node.edit_form', $path);
    $route = $service->getRouteByName('entity.node.edit_form');
    $this->assertEquals($path, $route->getPath());
  }

}
