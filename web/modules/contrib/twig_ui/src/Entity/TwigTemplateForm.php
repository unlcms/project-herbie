<?php

namespace Drupal\twig_ui\Entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\twig_ui\TemplateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Form controller for the Twig UI template edit/add forms.
 */
class TwigTemplateForm extends EntityForm {

  /**
   * The Twig UI Template Manager service.
   *
   * @var \Drupal\twig_ui\TemplateManager
   */
  protected $templateManager;

  /**
   * The entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a TwigTemplate Form object.
   *
   * @param \Drupal\twig_ui\TemplateManager $template_manager
   *   The Twig UI Template Manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(TemplateManager $template_manager, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $module_handler, MessengerInterface $messenger, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user) {
    $this->templateManager = $template_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig_ui.template_manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * The add/edit Twig Template entity form.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, FormStateInterface $form_state) {
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit Twig template</em> @label', ['@label' => $this->entity->label()]);
    }
    elseif ($this->operation == 'clone') {
      $form['#title'] = $this->t('<em>Clone Twig template</em> @label', ['@label' => $this->entity->label()]);

      // Alter entity values for clone.
      $this->entity->set('label', $this->t('Clone of @label', ['@label' => $this->entity->get('label')]));
      $this->entity->set('id', $this->t('clone_@id', ['@id' => $this->entity->getOriginalId()]));
      $this->entity->set('themes', []);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template name'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
    ];

    $form['theme_suggestion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme suggestion'),
      '#description' => $this->t('See <a href="@url" target="_blank">Twig Template naming conventions</a>.', ['@url' => 'https://www.drupal.org/docs/theming-drupal/twig-in-drupal/twig-template-naming-conventions']),
      '#default_value' => $this->entity->get('theme_suggestion'),
      '#required' => TRUE,
    ];

    $form['template_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template code'),
      '#default_value' => $this->entity->get('template_code'),
      '#required' => TRUE,
      '#wrapper_attributes' => [
        'class' => [
          'field-template-code',
        ],
      ],
    ];

    $twig_ui_config = $this->configFactory->get('twig_ui.settings');

    if ($this->moduleHandler->moduleExists('codemirror_editor')) {
      $form['template_code']['#type'] = 'codemirror';
      $form['template_code']['#codemirror'] = [
        'mode' => 'text/x-twig',
        'lineNumbers' => TRUE,
      ];

      $codemirror_config = Yaml::parse($twig_ui_config->get('codemirror_config', ''));
      // A populated config value will return an array when parsed. If the
      // config value is empty, then ::parse will return NULL, which will cause
      // array_merge to return NULL.
      if (is_array($codemirror_config)) {
        $form['template_code']['#codemirror'] = array_merge($form['template_code']['#codemirror'], $codemirror_config);
      }
    }

    $all_themes = $this->templateManager->getActiveThemes();
    $allowed_themes = $this->templateManager->getAllowedThemes();

    if ($this->operation == 'add') {
      // Auto-populate default selected themes for new config entities.
      $default_theme = $this->configFactory
        ->get('system.theme')
        ->get('default');

      $default_selected_themes = $twig_ui_config->get('default_selected_themes');

      // Merge in system default theme.
      if (in_array($default_theme, $allowed_themes)
        && in_array('_default', $default_selected_themes)
        ) {

        $default_selected_themes[] = $default_theme;
        $default_selected_themes = array_unique($default_selected_themes);
      }
      $themes_default_value = $default_selected_themes;
    }
    else {
      $themes_default_value = $this->entity->get('themes') ?: [];
    }

    // Build options list.
    $options = [];

    // Existing values for themes that are no longer on the allowed themes list
    // are grandfathered.
    $grandfathered_themes = FALSE;
    foreach ($all_themes as $theme_name => $theme) {
      // Add theme if in allowed theme list.
      if (in_array($theme_name, $allowed_themes)) {
        $options[$theme_name] = $theme->info['name'];
      }
      // Add theme if existing config value.
      elseif (in_array($theme_name, $themes_default_value)) {
        $options[$theme_name] = $theme->info['name'] . '*';
        $grandfathered_themes = TRUE;
      }
    }

    $form['themes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Themes'),
      '#options' => $options,
      '#default_value' => $themes_default_value,
    ];
    // Set description based on whether or not there are any
    // grandfathered themes.
    if ($grandfathered_themes) {
      $form['themes']['#description'] = $this->t('Themes for which this template should be used.<br>*If deselected, denoted themes will no longer be available as an option due to the <em>Allowed theme list</em> setting.');
    }
    else {
      $form['themes']['#description'] = $this->t('Themes for which this template should be used.');
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Whether this template is enabled or disabled.'),
      '#default_value' => $this->entity->get('status') ?? TRUE,
    ];

    if ($this->currentUser->hasPermission('load twig templates from file system')) {
      $form['template_load'] = [
        '#type' => 'details',
        '#title' => $this->t('Load Twig Template from File System'),
      ];

      $form['template_load']['intro'] = [
        '#markup' => $this->t("Find template code from Drupal's theme system by selecting a theme and a template theme suggestion."),
      ];

      $form['template_load']['theme'] = [
        '#type' => 'select',
        '#title' => $this->t('Theme'),
        '#options' => ['_none' => '- Select -'] + $options,
      ];
      $options = ['_none' => $this->t('Select a theme')];
      $form['template_load']['template'] = [
        '#type' => 'select',
        '#title' => $this->t('Template'),
        '#options' => $options,
      ];

      $form['template_load']['file_path'] = [
        '#markup' => $this->t('<div class="file-path"><span class="label">Template file path:</span> <span class="value"></span></div>'),
      ];

      $form['template_load']['template_code'] = [
        '#markup' => '<div class="template-code"><pre>' . $this->t('Select a template.') . '</pre></div>',
      ];

      $form['template_load']['insert'] = [
        '#type' => 'button',
        '#value' => $this->t('Insert'),
        '#name' => 'template-insert',
      ];

      $form['template_load']['insert_description'] = [
        '#markup' => $this->t('<div class="description insert-description">Clicking "Insert" will replace existing code in the <em>Template Code</em> field above.</div>'),
      ];

      $form['#attached']['library'][] = 'twig_ui/template_entity_form__template_load';
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Clear any form errors for the 'template' field since its value is only
    // used on the front end.
    $form_errors = $form_state->getErrors();
    $form_state->clearErrors();
    unset($form_errors['template']);
    foreach ($form_errors as $name => $error_message) {
      $form_state->setErrorByName($name, $error_message);
    }

    // Run 'themes' values through Checkboxes::getCheckedCheckboxes()
    // to remove unchecked values.
    if (!empty($form_state->getValue('themes'))) {
      $form_state->setValue('themes', Checkboxes::getCheckedCheckboxes($form_state->getValue('themes')));
    }
    // Convert status checkbox to boolean.
    $form_state->setValue('status', (boolean) $form_state->getValue(['status']));

    $values = $form_state->getValues();

    // If enabled, loop through submitted themes and check if the theme
    // suggestion is already registered for the selected themes.
    if ($values['status']) {
      foreach ($values['themes'] as $theme) {
        if ($template_id = $this->templateManager->templateExists($values['theme_suggestion'], $theme)) {
          if ($template_id != $this->entity->id()) {
            $theme_label = $this->templateManager->getActiveThemes()[$theme]->info['name'];

            $template = $this->templateManager->getTemplate($template_id);
            $template_url = $template->toLink(NULL, 'edit-form')->getUrl()->toString();
            $form_state->setError(
              $form['themes'],
              $this->t(
                'The <em>@suggestion</em> theme suggestion is already registered for the <em>@theme</em> theme by the <em><a href="@template_url">@template_label</a></em> Twig UI template. It must be disabled or deleted in order for this Twig UI template to be enabled.', [
                  '@suggestion' => $values['theme_suggestion'],
                  '@theme' => $theme_label,
                  '@template_label' => $template->label(),
                  '@template_url' => $template_url,
                ]
              )
            );
          }
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Set entity's original ID to the entity's ID.
    // \Drupal\Core\Entity\EntityStorageBase::doPreSave() will use the original
    // ID as the entity's ID (instead of the entity's current ID) when
    // processing the entity for presave.
    $this->entity->setOriginalId($this->entity->get('id'));
    $this->entity->save();
    $form_state->setRedirect('entity.twig_template.collection');
  }

  /**
   * Helper method to check whether a Twig UI template config entity exists.
   *
   * @param string $id
   *   A machine name.
   *
   * @return bool
   *   Whether or not the machine name already exists.
   */
  public function exist($id) {
    // The 'add' namespace is reserved.
    if ($id == 'add') {
      return TRUE;
    }
    $entity = $this->entityTypeManager->getStorage('twig_template')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    if ($this->operation == 'clone') {
      // Preserve original ID when duplicating.
      $original_id = $entity->get('id');
      $this->entity = $entity->createDuplicate();
      $this->entity->setOriginalId($original_id);
    }
    else {
      $this->entity = $entity;
    }
    return $this;
  }

}
