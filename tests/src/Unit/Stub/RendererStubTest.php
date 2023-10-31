<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Component\Render\MarkupInterface;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests RendererStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\RendererStub
 * @group test_helpers
 */
class RendererStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::doRender
   */
  public function testStub() {
    $service = TestHelpers::service('renderer');
    $build = [
      'element1' => [
        '#theme' => 'html_tag',
        '#tag' => 'h1',
        '#value' => 'Foo',
      ],
      'element2' => [
        '#theme' => 'items_list',
        '#items' => ['item1', 'item2'],
        '#cache' => [
          'tags' => ['node:1', 'node_list'],
        ],
      ],
    ];
    $cacheDefaults = [
      "tags" => [],
      "max-age" => -1,
      "contexts" => [],
    ];
    foreach ($build as $key => $value) {
      $buildRendered[$key] = $value;
      $buildRendered[$key]['#cache'] ??= [];
      $buildRendered[$key]['#cache'] += $cacheDefaults;
      $buildRendered[$key]['#attached'] ??= [];
      $buildRendered[$key]['#children'] ??= NULL;
      $buildRendered[$key]['#markup'] ??= '';
      $buildRendered[$key]['#printed'] ??= TRUE;
    }
    $buildRenderedString = '';
    foreach ($buildRendered as $key => $value) {
      $buildRenderedString .= json_encode($value);
    }
    $result = $service->render($build);
    $this->assertInstanceOf(MarkupInterface::class, $result);
    $this->assertSame($buildRenderedString, $result->__toString());
  }

}
