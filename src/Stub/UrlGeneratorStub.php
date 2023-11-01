<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A stub for the request_stack service.
 */
class UrlGeneratorStub extends UrlGenerator {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RouteProviderInterface $provider,
    OutboundPathProcessorInterface $path_processor,
    OutboundRouteProcessorInterface $route_processor,
    RequestStack $request_stack,
    array $filter_protocols = ['http', 'https']
  ) {
    TestHelpers::service('unrouted_url_assembler');
    TestHelpers::service('path.validator');
    parent::__construct($provider, $path_processor, $route_processor, $request_stack, $filter_protocols);
  }

}
