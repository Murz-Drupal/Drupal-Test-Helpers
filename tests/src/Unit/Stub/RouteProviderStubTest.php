<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\RouteProviderStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests UrlGeneratorStub class.
 *
 * @covers RouteProviderStub::stubAddRoute
 * @covers RouteProviderStub::stubAddRouteByPath
 */
#[CoversClass(RouteProviderStub::class)]
#[Group('test_helpers')]
class RouteProviderStubTest extends UnitTestCase {

  /**
   * Tests the RouteProviderStub methods.
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
