<?php

namespace Drupal\unl_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Recent News block.
 *
 * @Block(
 *   id = "unl_recent_news",
 *   admin_label = @Translation("Recent News"),
 *   category = @Translation("Aggregated Content"),
 * )
 */
class RecentNewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, PathValidatorInterface $path_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->pathValidator = $path_validator;
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
      $container->get('renderer'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'subhead' => '',
      'more_link' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['subhead'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subhead'),
      '#description' => $this->t('The subhead that will appear in the block above the news items.'),
      '#default_value' => $this->configuration['subhead'],
    ];

    $form['more_link'] = [
      '#type' => 'textfield',
      '#title' => 'More Link Path',
      '#default_value' => $this->configuration['more_link'],
      '#description' => $this->t('The absolute path for the "Read More News" link. E.g. "/news". The destination must already exist.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $more_link = $form_state->getValue('more_link');
    if (!$this->pathValidator->isValid($more_link)) {
      $form_state
        ->setErrorByName('more_link', $this->t('The path %path is not valid.', [
          '%path' => $more_link,
        ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['subhead'] = $form_state->getValue('subhead');
    $this->configuration['more_link'] = $form_state->getValue('more_link');
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
      ->range(0, 4)
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
      '#theme' => 'unl_news_recent_news_block',
      // #title will result in Drupal block title printing,
      // so use #subhead instead.
      '#subhead' => $this->configuration['subhead'],
      '#items' => $items,
      '#read_more' => $this->configuration['read_more'],
      '#cache' => [
        'tags' => ['node_list:news'],
      ],
    ];

    return $return;
  }

}
