<?php

namespace Drupal\test_helpers_example\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example pages to demonstrate the unit tests approach.
 */
class TestHelpersExampleController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * TestHelpersExampleController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The form builder.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  final public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    DateFormatterInterface $dateFormatter
  ) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Renders a list of two articles, reverse sorted by title.
   */
  public function articlesList() {
    $amount = $this->configFactory->get('test_helpers_example.settings')
      ->get('articles_to_display') ?? 3;

    $articlesIds = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck()
      ->condition('status', 1)
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, $amount)
      ->execute();

    $articles = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($articlesIds);

    $articlesList = [];
    foreach ($articles as $article) {
      $linkText = $this->t('@label (@date by @username)', [
        '@label' => $article->label(),
        '@date' => $this->dateFormatter->format($article->created->value),
        '@username' => $article->uid->entity->label(),
      ]);
      $articlesList[] = $article->toLink($linkText);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $articlesList,
      '#cache' => ['tags' => ['node_list:article']],
    ];
  }

}
