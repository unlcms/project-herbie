<?php

namespace Drupal\twig_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\twig_ui\TemplateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a config entity list form.
 */
class TwigTemplateListForm extends FormBase {

  /**
   * The Template Manager.
   *
   * @var \Drupal\twig_ui\TemplateManager
   */
  protected $templateManager;

  /**
   * The entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $twigUiTemplateStorage;

  /**
   * Class constructor.
   *
   * @param Drupal\twig_ui\TemplateManagerInterface $template_manager
   *   The Template Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   */
  public function __construct(TemplateManagerInterface $template_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->templateManager = $template_manager;
    $this->twigUiTemplateStorage = $entity_type_manager->getStorage('twig_template');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig_ui.template_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twig_ui_template_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Symfony\Component\HttpFoundation\ParameterBag */
    $parameters = $this->getRequest()->query;

    $form = [];

    $form['filters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
      '#attributes' => [
        'class' => [
          'filters-wrapper',
        ],
      ],
    ];
    $form['filters']['fields_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'fields-wrapper',
        ],
      ],
    ];
    $form['filters']['fields_wrapper']['template_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template Name'),
      '#default_value' => $parameters->has('label') ? $parameters->get('label') : '',
      '#size' => 30,
    ];
    $form['filters']['fields_wrapper']['template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine Name'),
      '#default_value' => $parameters->has('id') ? $parameters->get('id') : '',
      '#size' => 30,
    ];
    $form['filters']['fields_wrapper']['theme_suggestion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme Suggestion'),
      '#default_value' => $parameters->has('theme_suggestion') ? $parameters->get('theme_suggestion') : '',
      '#size' => 30,
    ];

    // Build list of themes from all utilized themes (i.e. all themes that are
    // used by at least one Twig UI template regardless of global
    // allowed status).
    $templates = $this->templateManager->getTemplates();
    $utilized_themes = [];
    foreach ($templates as $template) {
      $utilized_themes = array_merge($template->get('themes'), $utilized_themes);
    }
    $active_themes = $this->templateManager->getActiveThemes();
    $theme_options = [];
    foreach ($utilized_themes as $theme_name) {
      $theme_options[$theme_name] = $active_themes[$theme_name]->info['name'];
    }
    $form['filters']['fields_wrapper']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => $theme_options,
      '#empty_value' => '_none',
      '#default_value' => $parameters->has('theme') ? $parameters->get('theme') : '_none',
    ];
    $form['filters']['fields_wrapper']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        'enabled' => $this->t('Enabled'),
        'disabled' => $this->t('Disabled'),
      ],
      '#empty_value' => '_none',
      '#default_value' => $parameters->has('status') ? $parameters->get('status') : '_none',
    ];
    $form['filters']['actions_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'actions-wrapper',
        ],
      ],
    ];
    $form['filters']['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#name' => 'apply',
    ];
    $form['filters']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#name' => 'reset',
    ];

    $header = [
      // Make certain columns sortable.
      [
        'data' => $this->t('Template Name'),
        'specifier' => 'label',
        // Set default sort.
        // Note: Sort gets handled by the database, so it may be case sensitive
        // or it may be case insensitive.
        'sort' => 'asc',
      ],
      [
        'data' => $this->t('Machine Name'),
        'specifier' => 'id',
      ],
      [
        'data' => $this->t('Theme Suggestion'),
        'specifier' => 'theme_suggestion',
      ],
      [
        'data' => $this->t('Themes'),
      ],
      [
        'data' => $this->t('Status'),
      ],
      [
        'data' => $this->t('Operations'),
      ],
    ];

    /** @var \Drupal\Core\Config\Entity\Query\Query */
    $entity_query = $this->twigUiTemplateStorage->getQuery();
    $entity_query->pager(25);
    $entity_query->tableSort($header);

    // Apply filters as query conditions.
    if ($parameters->has('label')) {
      $entity_query->condition('label', $parameters->get('label'), 'CONTAINS');
    }
    if ($parameters->has('id')) {
      $entity_query->condition('id', $parameters->get('id'), 'CONTAINS');
    }
    if ($parameters->has('theme_suggestion')) {
      $entity_query->condition('theme_suggestion', $parameters->get('theme_suggestion'), 'CONTAINS');
    }
    if ($parameters->has('theme')) {
      // See
      // https://www.drupal.org/project/drupal/issues/2248567#comment-13080439.
      $entity_query->condition('themes.*', $parameters->get('theme'), '=');
    }
    if ($parameters->has('status')) {
      $status = ($parameters->get('status') == 'enabled') ? TRUE : FALSE;
      $entity_query->condition('status', $status, '=');
    }

    $ids = $entity_query->execute();
    $templates = $this->twigUiTemplateStorage->loadMultiple($ids);

    $rows = [];
    foreach ($templates as $entity) {
      $row = [];
      $row['label'] = $entity->label();
      $row['id'] = $entity->id();
      $row['theme_suggestion'] = $entity->get('theme_suggestion');

      $themes = $entity->get('themes');
      $row['themes'] = implode(', ', $themes);
      $row['status'] = ($entity->get('status')) ? 'Enabled' : 'Disabled';

      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => $entity->toUrl('edit-form'),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => $entity->toUrl('delete-form'),
            ],
            'clone' => [
              'title' => $this->t('Clone'),
              'url' => $entity->toUrl('clone-form'),
            ],
          ],
        ],
      ];

      $rows[] = $row;
    }

    $form['template_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $form['pager'] = [
      '#type' => 'pager',
    ];

    $form['#attributes']['class'][] = 'twig-ui-template-list-form';
    $form['#attached']['library'][] = 'twig_ui/list_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement()['#name'];

    $query_build = [];

    // If 'Apply' submit, then retain query string parameters.
    if ($triggering_element == 'apply') {
      /** @var \Symfony\Component\HttpFoundation\ParameterBag */
      $parameters = $this->getRequest()->query;

      $values = $form_state->getValues();

      if ($parameters->has('sort')) {
        $query_build['sort'] = $parameters->get('sort');
      }
      if ($parameters->has('order')) {
        $query_build['order'] = $parameters->get('order');
      }
      if ($values['template_label']) {
        $query_build['label'] = $values['template_label'];
      }
      if ($values['template_id']) {
        $query_build['id'] = $values['template_id'];
      }
      if ($values['theme_suggestion']) {
        $query_build['theme_suggestion'] = $values['theme_suggestion'];
      }
      if ($values['theme'] && $values['theme'] !== '_none') {
        $query_build['theme'] = $values['theme'];
      }
      if ($values['status'] && $values['status'] !== '_none') {
        $query_build['status'] = $values['status'];
      }
    }
    elseif ($triggering_element == 'reset') {
      // Nothing to do here. Wipe out the query string parameters.
    }

    $url = Url::fromRoute('entity.twig_template.collection', [], ['query' => $query_build]);
    $form_state->setRedirectUrl($url);
  }

}
