<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * A stub for the request_stack service.
 */
class RouteProviderStub extends RouteProvider {

  /**
   * {@inheritdoc}
   */
  public function preLoadRoutes($names) {
    // Just doing nothing.
  }

  /**
   * Adds a route to the collection.
   *
   * @param string $name
   *   The route name.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   */
  public function stubAddRoute(string $name, Route $route) {
    $this->routes[$name] = $route;
  }

  /**
   * Adds a route to the collection by a path string.
   *
   * @param string $name
   *   The route name.
   * @param string $path
   *   The route path.
   */
  public function stubAddRouteByPath(string $name, string $path) {
    $route = new Route($path);

    // @todo Try to simplify this block without manual assigning.
    $route->setOption('compiler_class', RouteCompiler::class);
    $variablesBag = new ParameterBag();
    $route->setDefaults(['_raw_variables' => $variablesBag]);

    $this->routes[$name] = $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoutesByPath($path) {
    $collection = new RouteCollection();
    foreach ($this->routes as $name => $route) {
      if ($route->getPath() == $path) {
        $collection->add($name, $route);
      }
    }
    return $collection;
  }

}
