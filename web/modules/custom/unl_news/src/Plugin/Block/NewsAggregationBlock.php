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
      'quantity' => 8,
      'tag' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('Items shown'),
      '#options' => [
        4 => '4',
        8 => '8',
        12 => '12',
        16 => '16',
      ],
      '#description' => $this->t('The number of news items to display.'),
      '#default_value' => $this->configuration['quantity'],
    ];


    $form['tag'] = [
      '#type' => 'select2',
      '#target_type' => 'taxonomy_term',
      '#title' => $this->t('Tag'),
      '#description' => $this->t('Tag to optionally filter the displayed news items.'),
      '#tags' => TRUE,
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['site_organization_tags'],
      ],
      '#multiple' => TRUE,
      '#autocomplete' => TRUE,
      '#default_value' => $this->configuration['tag'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['quantity'] = $form_state->getValue('quantity');
    $this->configuration['tag'] = $form_state->getCompleteFormState()->getUserInput()['settings']['tag'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load the view.
    $view = \Drupal\views\Views::getView('news_recent');
    if (!$view) {
      return [];
    }
    $selected_tags = $this->configuration['tag'] ?? [];
    $view->selected_tags = $this->configuration['tag'] ?? NULL;

    // Set the display ID.
    $display_id = 'block_1';
    $view->setDisplay($display_id);

    // Filter by selected tags.
    if ($selected_tags) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      if ($node_storage) {
      $query = $node_storage->getQuery();
      $query = $view->getQuery();
      $join = $this->getViewsPluginManager('join')->createInstance('standard', [
        'table' => 'taxonomy_index',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
        'field' => 'nid',
        'type' => 'INNER',
      ]);

      $query->addTable('taxonomy_index', 'node_field_data', $join);
      $query->addWhere('taxonomy_filter_group', 'taxonomy_index.tid', $selected_tags, 'IN');
     }
    }

    // Set items per page from configuration.
    $view->setItemsPerPage($this->configuration['quantity']);

    // Execute and render the view.
    $view->preExecute();
    $view->execute();
    return $view->render();
  }


  /**
   * Helper function to get the Views plugin manager.
   *
   * @param string $type
   * The type of plugin manager to get (e.g., 'join', 'filter').
   *
   * @return \Drupal\Core\Plugin\PluginManagerInterface
   * The plugin manager.
   */
  protected function getViewsPluginManager(string $type) {
    return \Drupal::service('plugin.manager.views.' . $type);
  }
}
