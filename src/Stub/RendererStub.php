<?php

namespace Drupal\test_helpers\Stub;

use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\PlaceholderGeneratorInterface;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A stub for the renderer service.
 *
 *  @package TestHelpers\DrupalServiceStubs
 */
class RendererStub extends Renderer {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    // Should be `ControllerResolverInterface|CallableResolver` for Drupal 11,
    // but to keep compatibility with Drupal 10 - do not strict check it.
    $controller_resolver,
    ThemeManagerInterface $theme,
    ElementInfoManagerInterface $element_info,
    PlaceholderGeneratorInterface $placeholder_generator,
    RenderCacheInterface $render_cache,
    RequestStack $request_stack,
    array $renderer_config
  ) {
    parent::__construct($controller_resolver, $theme, $element_info, $placeholder_generator, $render_cache, $request_stack, $renderer_config);
    $context = new RenderContext();
    $this->setCurrentRenderContext($context);
  }

  /**
   * {@inheritdoc}
   */
  protected function doRender(&$elements, $is_root_call = FALSE) {
    $result = parent::doRender($elements, $is_root_call);
    // If we have empty result, providing a json value of the elements.
    if ($result === '') {
      $result = json_encode($elements);
    }
    return $result;
  }

}
