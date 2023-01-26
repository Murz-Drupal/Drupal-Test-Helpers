<?php

namespace Drupal\test_helpers_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Example pages to demonstrate the unit tests approach.
 */
class TestHelpersExampleController extends ControllerBase {

  /**
   * Renders a list of two articles, reverse sorted by title.
   */
  public function articlesList() {
    $amount = \Drupal::config('my_site')->get('articles_to_display');

    $articlesIds = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, $amount)
      ->execute();

    $articles = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($articlesIds);

    $articlesList = [];
    foreach ($articles as $article) {
      $linkText = $article->label() . ' (' . \Drupal::service('date.formatter')->format($article->created->value) . ')';
      $articlesList[] = $article->toLink($linkText);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $articlesList,
    ];
  }

}
