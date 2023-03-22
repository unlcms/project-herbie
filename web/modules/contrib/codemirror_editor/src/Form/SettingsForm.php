<?php

namespace Drupal\codemirror_editor\Form;

use Drupal\codemirror_editor\CodemirrorModeManagerInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CodeMirror editor settings form.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The language mode manager.
   *
   * @var \Drupal\codemirror_editor\CodemirrorModeManagerInterface
   */
  protected $modeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'codemirror_editor_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['codemirror_editor.settings'];
  }

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\codemirror_editor\CodemirrorModeManagerInterface $mode_manager
   *   The language mode manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheTagsInvalidatorInterface $cache_tags_invalidator, CodemirrorModeManagerInterface $mode_manager) {
    parent::__construct($config_factory);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->modeManager = $mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache_tags.invalidator'),
      $container->get('plugin.manager.codemirror_mode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $settings = $this->config('codemirror_editor.settings')->get();

    $form['cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load the library from CDN'),
      '#default_value' => $settings['cdn'],
    ];

    $form['minified'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use minified version of the library'),
      '#default_value' => $settings['minified'],
    ];

    $codemirror_themes = static::getCodeMirrorThemes();
    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => $codemirror_themes,
      '#default_value' => $settings['theme'],
    ];

    $form['language_modes_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Language modes'),
      '#open' => TRUE,
    ];

    $header = [
      'label' => $this->t('Mode'),
      'mime_types' => [
        'data' => $this->t('Mime types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'dependencies' => $this->t('Dependencies'),
      'usage' => $this->t('Usage'),
    ];

    $options = [];
    $definitions = $this->modeManager->getDefinitions();
    foreach ($definitions as $mode => $definition) {
      $url = Url::fromUri(
        sprintf('https://codemirror.net/mode/%s/index.html', $mode),
        ['attributes' => ['target' => '_blank']]
      );

      $dependency_labels = [];
      foreach ($definition['dependencies'] as $dependency) {
        $dependency_labels[] = $definitions[$dependency]['label'];
      }

      $options[$mode] = [
        'label' => Link::fromTextAndUrl($definition['label'], $url),
        'mime_types' => implode(', ', $definition['mime_types']),
        'dependencies' => implode(', ', $dependency_labels),
        'usage' => implode(', ', $definition['usage']),
      ];
    }

    $form['language_modes_wrapper']['language_modes'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => array_fill_keys($settings['language_modes'], TRUE),
      '#suffix' => $this->t('Language modes required by modules are always loaded.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('codemirror_editor.settings')
      ->set('cdn', $values['cdn'])
      ->set('minified', $values['minified'])
      ->set('theme', $values['theme'])
      ->set('language_modes', array_values(array_filter($values['language_modes'])))
      ->save();

    // Invalidate discovery caches to rebuild asserts.
    $this->cacheTagsInvalidator->invalidateTags(['library_info']);

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns available CodeMirror themes.
   *
   * @return array
   *   CodeMirror themes.
   */
  protected static function getCodeMirrorThemes() {
    return [
      'default' => 'Default',
      '3024-day' => '3024 day',
      '3024-night' => '3024 night',
      'abcdef' => 'ABCDEF',
      'ambiance' => 'Ambiance',
      'base16-dark' => 'Base16 dark',
      'base16-light' => 'Base16 light',
      'bespin' => 'Bespin',
      'blackboard' => 'Black board',
      'cobalt' => 'Cobalt',
      'colorforth' => 'Color forth',
      'darcula' => 'Darcula',
      'dracula' => 'Dracula',
      'duotone-dark' => 'Duotone dark',
      'eclipse' => 'Eclipse',
      'elegant' => 'Elegant',
      'erlang-dark' => 'Erlang dark',
      'gruvbox-dark' => 'Gruvbox dark',
      'hopscotch' => 'Hopscotch',
      'icecoder' => 'Ice coder',
      'idea' => 'Idea',
      'isotope' => 'Isotope',
      'lesser-dark' => 'Lesser dark',
      'liquibyte' => 'Liquibyte',
      'lucario' => 'Lucario',
      'material' => 'Material',
      'mbo' => 'MBO',
      'mdn-like' => 'MDN like',
      'midnight' => 'Midnight',
      'monokai' => 'Monokai',
      'neat' => 'Neat',
      'neo' => 'Neo',
      'night' => 'Night',
      'oceanic-next' => 'Oceanic next',
      'panda-syntax' => 'Panda syntax',
      'paraiso-dark' => 'Paraiso dark',
      'paraiso-light' => 'Paraiso light',
      'pastel-on-dark' => 'Pastel on dark',
      'railscasts' => 'Rails casts',
      'rubyblue' => 'Ruby blue',
      'seti' => 'Seti',
      'shadowfox' => 'Shadow fox',
      'solarized-dark' => 'Solarized dark',
      'solarized-light' => 'Solarized light',
      'the-matrix' => 'The matrix',
      'tomorrow-night-bright' => 'Tomorrow night bright',
      'tomorrow-night-eighties' => 'Tomorrow night eighties',
      'ttcn' => 'TTCN',
      'twilight' => 'Twilight',
      'vibrant-ink' => 'Vibrant ink',
      'xq-dark' => 'XQ dark',
      'xq-light' => 'XQ light',
      'yeti' => 'Yeti',
      'zenburn' => 'Zenburn',
    ];

  }

}
