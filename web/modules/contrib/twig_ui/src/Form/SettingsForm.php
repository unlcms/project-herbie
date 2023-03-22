<?php

namespace Drupal\twig_ui\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\twig_ui\TemplateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * The settings form for the Twig UI module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Twig UI Template Manager service.
   *
   * @var \Drupal\twig_ui\TemplateManager
   */
  protected $templateManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Whether CodeMirror Editor is installed.
   *
   * @var bool
   */
  protected $codeMirror = FALSE;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\twig_ui\TemplateManager $template_manager
   *   The Twig UI Template Manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(TemplateManager $template_manager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler) {
    $this->templateManager = $template_manager;
    $this->messenger = $messenger;

    if ($module_handler->moduleExists('codemirror_editor')) {
      $this->codeMirror = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig_ui.template_manager'),
      $container->get('messenger'),
      $container->get('module_handler')
    );
  }

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'twig_ui.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twig_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // Build options array from active themes.
    $active_theme_list = $this->templateManager->getActiveThemes();
    $options = [];
    foreach ($active_theme_list as $theme_name => $theme) {
      $options[$theme_name] = $theme->info['name'];
    }

    $form['allowed_themes'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allowed themes'),
      '#options' => [
        'all' => $this->t('All themes allowed'),
        'selected' => $this->t('Selected themes allowed'),
      ],
      '#default_value' => $config->get('allowed_themes') ?: 'all',
    ];

    $form['allowed_theme_list'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed theme list'),
      '#description' => $this->t('The themes selected here will be available as options for Twig UI templates.'),
      '#options' => $options,
      '#default_value' => $config->get('allowed_theme_list') ?: [],
      '#states' => [
        'visible' => [
          ':input[name="allowed_themes"]' => ['value' => 'selected'],
        ],
      ],
    ];

    // Add default system theme option to options list.
    $options = array_merge(['_default' => 'Default system theme'], $options);

    $form['default_selected_themes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default theme(s)'),
      '#description' => $this->t('The themes selected here will be pre-selected as the default theme values for new Twig UI templates. If selecting the default system theme, make sure it is listed as an allowed theme.'),
      '#options' => $options,
      '#default_value' => $config->get('default_selected_themes') ?: [],
    ];

    if ($this->codeMirror) {
      $form['codemirror_config'] = [
        '#type' => 'codemirror',
        '#title' => $this->t('CodeMirror configuration'),
        '#codemirror' => [
          'mode' => 'text/x-yaml',
          'lineNumbers' => TRUE,
          'toolbar' => FALSE,
        ],
        '#description' => $this->t('Enter CodeMirror configuration as YAML. For example:<br><code>lineNumbers: false<br>buttons:<br>&nbsp;&nbsp;- bold<br>&nbsp;&nbsp;- italic</code><br>See the <a href="@url" target="_blank">CodeMirror manual</a> for more configuration options.', ['@url' => 'https://codemirror.net/doc/manual.html']),
        '#default_value' => $config->get('codemirror_config', ''),
      ];
    }

    $form['#attached']['library'][] = 'twig_ui/settings_form';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Run 'themes' values through Checkboxes::getCheckedCheckboxes()
    // to remove unchecked values.
    if (!empty($form_state->getValue('allowed_theme_list'))) {
      $form_state->setValue('allowed_theme_list', Checkboxes::getCheckedCheckboxes($form_state->getValue('allowed_theme_list')));
    }
    if (!empty($form_state->getValue('default_selected_themes'))) {
      $form_state->setValue('default_selected_themes', Checkboxes::getCheckedCheckboxes($form_state->getValue('default_selected_themes')));
    }

    try {
      Yaml::Parse($form_state->getValue('codemirror_config', ''));
    }
    catch (ParseException $e) {
      $form_state->setErrorByName('codemirror_config', 'Invalid YAML CodeMirror Configuration field');
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $allowed_themes = $form_state->getValue('allowed_themes');
    $allowed_theme_list = $form_state->getValue('allowed_theme_list');
    $default_selected_themes = $form_state->getValue('default_selected_themes');

    // Remove default selected themes if they're not in the allowed list.
    if ($allowed_themes == 'selected') {
      // Keep track of removed themes.
      $removed_default_selected_themes = [];
      foreach ($default_selected_themes as $key => $default_selected_theme) {
        if (!in_array($default_selected_theme, $allowed_theme_list)
          && $default_selected_theme !== '_default'
          ) {
          unset($default_selected_themes[$key]);
          $removed_default_selected_themes[] = $default_selected_theme;
        }
      }
      if (!empty($removed_default_selected_themes)) {
        $removed_default_selected_themes = implode(', ', $removed_default_selected_themes);
        $this->messenger->addStatus('The following themes were not saved as "Default themes" because they were not listed on the "Allowed theme list": ' . $removed_default_selected_themes);
      }
    }

    $config = $this->configFactory->getEditable(static::SETTINGS)
      ->set('allowed_themes', $allowed_themes)
      ->set('allowed_theme_list', $allowed_theme_list)
      ->set('default_selected_themes', $default_selected_themes);

    if ($this->codeMirror) {
      // Standardize new lines before storing.
      $codemirror_config = str_replace(["\r\n", "\r"], "\n", $form_state->getValue('codemirror_config', ''));
      $config->set('codemirror_config', $codemirror_config);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
