<?php

namespace Drupal\unl_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a News Aggregation block.
 *
 * @Block(
 *   id = "unl_news_aggregation",
 *   admin_label = @Translation("News Aggregation"),
 *   category = @Translation("Aggregated Content"),
 * )
 */
class NewsAggregationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quantity' => 16,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('Items per page'),
      '#options' => [
        12 => '12',
        16 => '16',
        20 => '20',
      ],
      '#description' => $this->t('The number of news items to display per page'),
      '#default_value' => $this->configuration['quantity'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['quantity'] = $form_state->getValue('quantity');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery();

    $ids = $query->condition('status', 1)
      ->accessCheck(FALSE)
      ->condition('type', 'news')
      ->sort('created', 'DESC')
      ->pager($this->configuration['quantity'])
      ->execute();
    $articles = $node_storage->loadMultiple($ids);

    $items = [];
    foreach ($articles as $nid => $article) {
      $items[$nid]['title'] = $article->get('title')->getString();

      // Determine URL, depending on whether local content or remote
      // (Nebraska Today) content.
      if ($canonical_url = $article->get('n_news_canonical_url')->getString()) {
        $items[$nid]['link'] = $canonical_url;
        $items[$nid]['source'] = 'remote';
      }
      else {
        $items[$nid]['link'] = $article->toLink(NULL, 'canonical', ['absolute' => TRUE])->getUrl();
        $items[$nid]['source'] = 'local';
      }

      if ($article->get('n_news_image')->getValue()) {
        $items[$nid]['image'] = $article->get('n_news_image')->view('teaser');
      }
    }

    $return = [
      '#theme' => 'unl_news_news_aggregation_block',
      '#items' => $items,
      '#pager' => [
        '#type' => 'pager',
      ],
      '#cache' => [
        'tags' => ['node_list:news'],
      ],
    ];

    return $return;
  }

}
