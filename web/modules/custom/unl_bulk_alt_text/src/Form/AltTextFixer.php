<?php

namespace Drupal\unl_bulk_alt_text\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AltTextFixer extends FormBase {

  /**
   * The AI LLM Provider Helper.
   *
   * @var \Drupal\ai\AiProviderHelper
   */
  protected $aiProviderHelper;

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The AI Provider.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $providerManager;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle info interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_bulk_alt_text_fixer';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->aiProviderHelper = $container->get('ai.form_helper');
    $instance->requestStack = $container->get('request_stack');
    $instance->providerManager = $container->get('ai.provider');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->fileSystem = $container->get('file_system');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->bundleInfo = $container->get('entity_type.bundle.info');
    $instance->database = $container->get('database');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->messenger = $container->get('messenger');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Only retrieve the results needed for the current page.
    $image_fields = $this->getEntitiesAndFields(50);

    // If there are no image fields, return a message.
    if (empty($image_fields)) {
      $form['message'] = [
        '#markup' => $this->t('<strong>You are good!</strong> No image fields found with missing or short alt text.'),
      ];

      return $form;
    }

    $form['message'] = [
      '#markup' => '<p>' . $this->t('Below are all images that are either missing alt text, the alt text is less than ten characters, or the alt text starts with the superfluous "Image of" or "Photo of".') . '</p>',
    ];

    $header = [
      'image' => $this->t('Image'),
      'entity_type' => $this->t('Entity'),
      'suggested_alt_text' => $this->t('Alt Text'),
      'actions' => $this->t('Actions'),
    ];

    $form['suggest'] = [
      '#type' => 'button',
      '#value' => $this->t('Bulk Generate Alt Text with AI'),
      '#attributes' => [
        'class' => ['suggest-alt-text'],
      ],
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
      '#input' => TRUE,
    ];

    foreach ($image_fields as $i => $image) {
      $form['table'][$i] = [
        'image' => [
          '#theme' => 'image_style',
          '#style_name' => 'large',
          '#uri' => $image['entity']->getFileUri(),
          '#alt' => $image['entity']->getFilename(),
        ],

        'entity_type' => [
          '#markup' => substr($image['base_entity']->label(), 0, 30) . ' (' . $image['field'] . ')',
        ],

        'suggested_alt_text' => [
          '#type' => 'textarea',
          '#default_value' => $image['existing_alt_text'],
          '#attributes' => [
            'class' => [
              'alt-text-' . $image['unique_id'],
              'alt-text-textarea',
            ],
          ],
          '#suffix' => '<div class="textarea-loader load-' . $image['unique_id'] . '"><div class="loader"></div></div>',
        ],

        'actions' => [
          '#type' => 'button',
          '#value' => $this->t('Generate with AI'),
          '#attributes' => [
            'class' => ['alt-text-item'],
            'data-unique-id' => $image['unique_id'],
            'data-entity-language' => $image['langcode'],
            'data-file-id' => $image['entity']->id(),
          ],
        ],

        'base_entity_id' => [
          '#type' => 'hidden',
          '#value' => $image['base_entity']->id(),
        ],

        'base_entity_type' => [
          '#type' => 'hidden',
          '#value' => $image['base_entity']->getEntityTypeId(),
        ],

        'field' => [
          '#type' => 'hidden',
          '#value' => $image['field'],
        ],

        'delta' => [
          '#type' => 'hidden',
          '#value' => $image['delta'],
        ],

        'langcode' => [
          '#type' => 'hidden',
          '#value' => $image['langcode'],
        ],
      ];
    }

    // Render Drupal's pager.
    $form['pager'] = [
      '#type' => 'pager',
    ];

    $form['#attached']['library'][] = 'unl_bulk_alt_text/suggest';

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $descriptions = $form_state->getValue('table');
    $count = 0;

    foreach ($descriptions as $row) {
      if (empty($row['suggested_alt_text'])) {
        continue;
      }

      $storage = $this->entityTypeManager->getStorage($row['base_entity_type']);

      $entity = $storage->load($row['base_entity_id']);

      if (!$entity) {
        continue;
      }

      // Update the translation that was displayed on the form.
      if (
        !empty($row['langcode'])
        && $entity->isTranslatable()
        && $entity->hasTranslation($row['langcode'])
      ) {
        $entity = $entity->getTranslation($row['langcode']);
      }

      // Update the specific image item represented by this row.
      if (isset($entity->{$row['field']}[$row['delta']])) {
        $entity->{$row['field']}[$row['delta']]->alt = $row['suggested_alt_text'];

        $entity->save();
        $count++;
      }
    }

    $this->messenger->addMessage($this->t('@count alt text fields have been updated.', ['@count' => $count]));
  }

  /**
   * Gets a paginated list of entities and fields with bad alt text.
   *
   * The database query finds matching image field rows and applies bundle,
   * language, and revision filtering before the pager is applied.
   *
   * @param int $limit
   *   The number of results to display per page.
   *
   * @return array
   *   An array of entities and fields for the current page.
   */
  protected function getEntitiesAndFields($limit = 50) {
    $image_fields = $this->getImageFields();

    $union = NULL;

    foreach ($image_fields as $field) {
      $table = $field['table'];
      $entity_type_id = $field['entity_type'];
      $bundle = $field['bundle'];
      $field_name = $field['field'];

      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      $base_table = $entity_type->getBaseTable();
      $id_key = $entity_type->getKey('id');
      $bundle_key = $entity_type->getKey('bundle');
      $langcode_key = $entity_type->getKey('langcode');
      $revision_key = $entity_type->getKey('revision');

      if (!$base_table || !$id_key) {
        continue;
      }

      $target_column = $field_name . '_target_id';
      $alt_column = $field_name . '_alt';

      $query = $this->database->select($table, 'f');

      /*
       * Join the field table to the entity base table.
       *
       * Bundle filtering is done here rather than after pagination. This
       * prevents invalid rows from consuming slots on a page.
       */
      $join_condition = 'b.' . $id_key . ' = f.entity_id';

      /*
       * If the entity is revisionable, make sure we are looking at the
       * current/default revision represented by the base table.
       *
       * Without this condition, a revisionable entity can have multiple
       * matching rows in the field table for old revisions.
       */
      if ($revision_key) {
        $join_condition .= ' AND b.' . $revision_key . ' = f.revision_id';
      }

      /*
       * Match the field's language to the entity's language where both
       * tables have a langcode column.
       */
      if ($langcode_key) {
        $join_condition .= ' AND b.' . $langcode_key . ' = f.langcode';
      }

      $query->innerJoin(
        $base_table,
        'b',
        $join_condition
      );

      $query->addField('f', 'entity_id', 'entity_id');
      $query->addField('f', 'delta', 'delta');
      $query->addField('f', $target_column, 'target_id');
      $query->addField('f', $alt_column, 'alt');

      /*
       * The UNION requires every SELECT to return the same columns.
       */
      $query->addExpression(
        ':entity_type',
        'entity_type',
        [
          ':entity_type' => $entity_type_id,
        ]
      );

      $query->addExpression(
        ':bundle',
        'bundle',
        [
          ':bundle' => $bundle,
        ]
      );

      $query->addExpression(
        ':field_name',
        'field_name',
        [
          ':field_name' => $field_name,
        ]
      );

      /*
       * Field tables have langcode. Keep it in the result so the correct
       * translation can be edited when the form is submitted.
       */
      $query->addField('f', 'langcode', 'langcode');

      // Restrict this query to the specific bundle.
      if ($bundle_key) {
        $query->condition('b.' . $bundle_key, $bundle);
      }

      // Ignore deleted field rows.
      $query->condition('f.deleted', 0);

      /*
       * Find rows with problematic alt text:
       *
       * 1. NULL.
       * 2. Empty.
       * 3. Fewer than 10 characters.
       * 4. Starts with "image".
       * 5. Starts with "photo".
       */
      $or = $query->orConditionGroup();

      $or->isNull('f.' . $alt_column);

      $or->condition(
        'f.' . $alt_column,
        '',
        '='
      );

      $or->where(
        'CHAR_LENGTH(f.' . $alt_column . ') < 10'
      );

      $or->where(
        'LOWER(f.' . $alt_column . ') LIKE :image_prefix',
        [
          ':image_prefix' => 'image%',
        ]
      );

      $or->where(
        'LOWER(f.' . $alt_column . ') LIKE :photo_prefix',
        [
          ':photo_prefix' => 'photo%',
        ]
      );

      $query->condition($or);

      if ($union === NULL) {
        $union = $query;
      }
      else {
        $union->union($query, 'UNION ALL');
      }
    }

    if ($union === NULL) {
      return [];
    }

    /*
     * Give the combined result set a deterministic order.
     *
     * The pager is applied to the UNION query, meaning only $limit rows
     * are returned to PHP for the current page.
     */
    $union->orderBy('entity_type');
    $union->orderBy('entity_id');
    $union->orderBy('field_name');
    $union->orderBy('delta');
    $union->orderBy('langcode');

    $pager = $union->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit);

    $results = $pager->execute()->fetchAll();

    if (empty($results)) {
      return [];
    }

    /*
     * Group entity IDs by entity type so we can load entities in batches.
     */
    $entity_ids_by_type = [];

    foreach ($results as $result) {
      $entity_ids_by_type[$result->entity_type][] = $result->entity_id;
    }

    $loaded_entities = [];

    foreach ($entity_ids_by_type as $entity_type => $entity_ids) {
      $loaded_entities[$entity_type] = $this->entityTypeManager->getStorage($entity_type)->loadMultiple(array_unique($entity_ids));
    }

    /*
     * Load all files needed for the current page in one operation.
     */
    $file_ids = [];

    foreach ($results as $result) {
      if (!empty($result->target_id)) {
        $file_ids[] = $result->target_id;
      }
    }

    $files = [];

    if (!empty($file_ids)) {
      $files = $this->entityTypeManager->getStorage('file')->loadMultiple(array_unique($file_ids));
    }

    $items = [];

    foreach ($results as $result) {
      $base_entity = $loaded_entities[$result->entity_type][$result->entity_id] ?? NULL;
      $file = $files[$result->target_id] ?? NULL;

      if (!$base_entity || !$file) {
        continue;
      }

      $items[] = [
        'entity' => $file,
        'field' => $result->field_name,
        'type' => $result->entity_type,
        'base_entity' => $base_entity,
        'bundle' => $result->bundle,
        'delta' => (int) $result->delta,
        'langcode' => $result->langcode ?: $base_entity->language()->getId(),
        'existing_alt_text' => $result->alt,
        'unique_id' => md5(
          $result->entity_id
          . '-'
          . $result->field_name
          . '-'
          . $result->delta
          . '-'
          . $result->target_id
          . '-'
          . $result->langcode
        ),
      ];
    }

    return $items;
  }

  /**
   * Get the list of all image fields for all entity types and bundles.
   *
   * @return array
   *   An array of image fields.
   */
  protected function getImageFields() {
    $fields = [];
    $entity_types = $this->getEntityTypes();

    foreach ($entity_types as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle_id => $bundle) {
        foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id) as $field_name => $field_definition) {
          if ($field_definition->getType() !== 'image') {
            continue;
          }

          // Check the config if alt text is enabled.
          $config = $this->config(
            'field.field.'
            . $entity_type_id
            . '.'
            . $bundle_id
            . '.'
            . $field_name
          );

          if (!$config->get('settings.alt_field')) {
            continue;
          }

          /*
           * Use a unique key for the entity type, bundle, and field.
           *
           * Multiple bundles can use the same field storage table, so using
           * only the table name as the array key could cause one bundle to
           * overwrite another.
           */
          $key = $entity_type_id . ':' . $bundle_id . ':' . $field_name;

          $fields[$key] = [
            'table' => $entity_type_id . '__' . $field_name,
            'entity_type' => $entity_type_id,
            'bundle' => $bundle_id,
            'field' => $field_name,
          ];
        }
      }
    }

    return $fields;
  }

  /**
   * Get a list of all content entities and bundles.
   *
   * @return array
   *   An array of entity types and bundles.
   */
  protected function getEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $bundles = [];

    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Check if it is a content entity type.
      if (!$entity_type->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface')) {
        continue;
      }

      $bundles[$entity_type_id] = $this->bundleInfo->getBundleInfo($entity_type_id);
    }

    return $bundles;
  }

}

