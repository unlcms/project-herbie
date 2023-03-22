<?php

namespace Drupal\feeds\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\CustomSource\CustomSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list of custom sources.
 */
class CustomSourceListController extends ControllerBase {

  /**
   * The feed type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $feedTypeStorage;

  /**
   * The plugin manager for custom sources.
   *
   * @var \Drupal\feeds\Plugin\Type\FeedsPluginManager
   */
  protected $customSourcePluginManager;

  /**
   * Constructs a new CustomSourcesListController object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $feed_type_storage
   *   The feed type storage.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $custom_source_plugin_manager
   *   The plugin manager for custom sources.
   */
  public function __construct(ConfigEntityStorageInterface $feed_type_storage, PluginManagerInterface $custom_source_plugin_manager) {
    $this->feedTypeStorage = $feed_type_storage;
    $this->customSourcePluginManager = $custom_source_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('feeds_feed_type'),
      $container->get('plugin.manager.feeds.custom_source')
    );
  }

  /**
   * Page title callback.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feeds_feed_type
   *   The feed type to display custom sources for.
   *
   * @return string
   *   The title of the source list page.
   */
  public function title(FeedTypeInterface $feeds_feed_type) {
    return $this->t('Custom sources for @label', ['@label' => $feeds_feed_type->label()]);
  }

  /**
   * Page callback.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feeds_feed_type
   *   The feed type to display custom sources for.
   *
   * @return array
   *   A render array.
   */
  public function page(FeedTypeInterface $feeds_feed_type) {
    $this->feedType = $feeds_feed_type;

    $header_front = [
      'label' => $this->t('Label'),
      'value' => $this->t('Value'),
    ];
    $header_back = [
      'operations' => $this->t('Operations'),
    ];

    $return = [];
    foreach ($this->feedType->getCustomSources() as $key => $source) {
      if (!strlen($key)) {
        continue;
      }

      $type = $source['type'] ?? 'blank';

      try {
        $custom_source_plugin = $this->customSourcePluginManager->createInstance($type, [
          'feed_type' => $this->feedType,
        ]);

        $additional_columns = $custom_source_plugin->additionalColumns($source);
        $header_middle = [];
        foreach ($additional_columns as $column_key => $column) {
          $header_middle[$column_key] = $column['#header'];
        }

        $header = $header_front + $header_middle + $header_back;
        if (!isset($return[$type])) {
          $return[$type . '_header'] = [
            '#type' => 'html_tag',
            '#tag' => 'h4',
            '#value' => $this->t('Custom %type sources', ['%type' => $custom_source_plugin->getLabel()]),
          ];
          $return[$type] = [
            '#type' => 'table',
            '#header' => $header,
          ];
        }

        $return[$type][$key] = $this->buildRow($source, $custom_source_plugin);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
      }
    }

    if (empty($return)) {
      $return = [
        '#markup' => $this->t('There are no custom sources yet. You can add one on the Mapping form. From the source selector, choose "New [type] source...", where "[type]" is the type of source to add.'),
      ];
    }

    return $return;
  }

  /**
   * Builds a single source row.
   *
   * @param array $source
   *   A single custom source, which is expected to consist of at least the
   *   following:
   *   - label;
   *   - value;
   *   - machine_name.
   * @param \Drupal\feeds\Plugin\Type\CustomSource\CustomSourceInterface $custom_source_plugin
   *   The custom source plugin for which to build the row.
   *
   * @return array
   *   The form structure for a single mapping row.
   */
  protected function buildRow(array $source, CustomSourceInterface $custom_source_plugin) {
    $row = [
      'label' => [
        '#plain_text' => $source['label'],
      ],
      'value' => [
        '#plain_text' => $source['value'],
      ],
    ];

    $type = $source['type'] ?? 'blank';
    $additional_columns = $custom_source_plugin->additionalColumns($source);
    foreach ($additional_columns as $key => $column) {
      $row[$key] = $column['#value'];
    }

    $url_parameters = [
      'feeds_feed_type' => $this->feedType->id(),
    ];
    $operations_params = $url_parameters + [
      'key' => $source['machine_name'],
    ];

    $row['operations'] = [
      '#type' => 'operations',
      '#links' => [
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('entity.feeds_feed_type.source_edit', $operations_params, [
            'query' => [
              'destination' => Url::fromRoute('entity.feeds_feed_type.sources', $url_parameters)->toString(),
            ],
          ]),
        ],
        'delete' => [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('entity.feeds_feed_type.source_delete', $operations_params),
        ],
      ],
    ];

    return $row;
  }

}
