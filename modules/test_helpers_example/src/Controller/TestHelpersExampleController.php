<?php

namespace Drupal\test_helpers_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Example pages to demonstrate the unit tests approach.
 */
class TestHelpersExampleController extends ControllerBase {

  /**
   * Renders a list of last articles.
   */
  public function articlesList() {
    $articlesIds = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, 3)
      ->execute();

    $articles = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($articlesIds);

    $articlesList = [];
    foreach ($articles as $article) {
      $linkText = $article->label() . ' (' . $article->id() . ')';
      $articlesList[] = $article->toLink($linkText);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $articlesList,
    ];
  }

}
