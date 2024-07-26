<?php

namespace Drupal\test_helpers_example;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Example service to demonstrate usage of Test Helpers API.
 */
class ArticlesManagerService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ArticlesManagerService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Returns an array with articles info, translated to the required language.
   *
   * @param string $langcode
   *   The langcode to use for translation.
   *
   * @return array
   *   The array of articles.
   */
  public function getTranslatedArticlesList(string $langcode) {
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple();
    foreach ($nodes as $node) {
      if (!$node->hasTranslation($langcode)) {
        continue;
      }
      $node = $node->getTranslation($langcode);
      $category = $node->field_category->entity;
      $result[] = [
        'title' => $node->label(),
        'author' => $node->uid->entity ? $node->uid->entity->label() : NULL,
        'term' => $category->vid->entity->label() . ': ' . $category->label(),
        'synopsis' => $node->field_synopsis->value,
      ];
    }
    return $result;
  }

}
