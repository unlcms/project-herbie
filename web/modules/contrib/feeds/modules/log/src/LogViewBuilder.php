<?php

namespace Drupal\feeds_log;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\feeds_log\Form\FilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Entity view builder for feeds_import_log entities.
 *
 * This lists all log entries for a single import.
 */
class LogViewBuilder extends EntityViewBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $builder = parent::createInstance($container, $entity_type);
    $builder->setEntityTypeManager($container->get('entity_type.manager'));
    $builder->setDateFormatter($container->get('date.formatter'));
    $builder->setFileUrlGenerator($container->get('file_url_generator'));
    $builder->setFormBuilder($container->get('form_builder'));
    $builder->setRequest($container->get('request_stack')->getCurrentRequest());

    return $builder;
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Sets the date formatter service.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function setDateFormatter(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Sets the file url generator.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator.
   */
  public function setFileUrlGenerator(FileUrlGeneratorInterface $file_url_generator) {
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Sets the form builder service.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function setFormBuilder(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * Sets the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);

    // Filters.
    $build['feeds_log_filter_form'] = $this->formBuilder->getForm(FilterForm::class);

    // Header.
    $header = [
      'timestamp' => [
        'data' => $this->t('Time'),
        'field' => 'log.timestamp',
        'sort' => 'desc',
      ],
      'operation' => [
        'data' => $this->t('Operation'),
        'field' => 'log.operation',
      ],
      'message' => [
        'data' => $this->t('Message'),
        'field' => 'log.message',
      ],
      'entity' => [
        'data' => $this->t('Entity'),
      ],
      'item' => [
        'data' => $this->t('Source item'),
      ],
      'item_id' => [
        'data' => $this->t('Item ID'),
      ],
    ];

    $options = [
      'header' => $header,
      'limit' => 50,
    ];

    $conditions = $this->buildFilterConditions();
    if (!empty($conditions)) {
      $options['conditions'] = $conditions;
    }

    // Rows.
    $rows = [];
    foreach ($entity->getLogEntries($options) as $log) {
      $row = [];
      $row['timestamp'] = $this->dateFormatter->format($log->timestamp, 'short');
      $row['operation'] = $log->operation;
      $row['message'] = [
        'data' => [
          '#markup' => $this->t($log->message, $log->variables),
        ],
      ];

      if (!empty($log->entity_id) && !empty($log->entity_type_id)) {
        $entity_type = $log->entity_type_id;
        $entity_id = $log->entity_id;
        $target_entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
        if ($target_entity) {
          $row['entity'] = ($target_entity->hasLinkTemplate('canonical')) ? $target_entity->toLink() : "$entity_type:$entity_id";
        }
        elseif (!empty($log->entity_label)) {
          $row['entity'] = "{$log->entity_label} ($entity_id)";
        }
        else {
          $row['entity'] = "$entity_type:$entity_id";
        }
      }
      else {
        $row['entity'] = $log->entity_label;
      }

      if (!empty($log->item) && file_exists($log->item)) {
        $row['item']['data'] = [
          '#type' => 'link',
          '#url' => Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($log->item)),
          '#title' => $this->t('View'),
          '#attributes' => [
            'target' => '_blank',
          ],
        ];
      }
      else {
        $row['item'] = NULL;
      }

      $row['item_id'] = $log->item_id;

      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('No log messages available.'),
    ];
    $build['pager'] = ['#type' => 'pager'];

    // Disable caching.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Builds conditions to pass to the query for selecting log entries.
   *
   * @return array|null
   *   An associative array where the key represents the field the condition
   *   applies on and the value consists of the following:
   *   - value (string|array|\Drupal\Core\Database\Query\SelectInterface|null):
   *     the value to test the field against;
   *   - operator (string|null): the operator to use.
   */
  protected function buildFilterConditions() {
    $session_filters = $this->request->getSession()->get('feeds_log_filter', []);
    if (empty($session_filters)) {
      return;
    }

    $filters = FilterForm::getFilters();
    $conditions = [];
    foreach ($session_filters as $key => $filter) {
      if (!isset($filters[$key])) {
        continue;
      }

      if (count($filter) === 1) {
        $conditions[$key] = [
          'value' => reset($filter),
        ];
      }
      elseif (count($filter) > 1) {
        $conditions[$key] = [
          'value' => $filter,
          'operator' => 'IN',
        ];
      }
    }

    return $conditions;
  }

}
