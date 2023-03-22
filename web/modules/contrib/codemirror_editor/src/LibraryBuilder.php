<?php

namespace Drupal\codemirror_editor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * CodeMirror library builder.
 */
class LibraryBuilder implements LibraryBuilderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language mode manager.
   *
   * @var \Drupal\codemirror_editor\CodemirrorModeManagerInterface
   */
  protected $modeManager;

  /**
   * Constructs a CodeMirrorLibraryBuilder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\codemirror_editor\CodemirrorModeManagerInterface $mode_manager
   *   The language mode manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, CodemirrorModeManagerInterface $mode_manager) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->modeManager = $mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $settings = $this->configFactory->get('codemirror_editor.settings')->get();

    $library = [
      'remote' => 'https://codemirror.net',
      'version' => static::CODEMIRROR_VERSION,
      'license' => [
        'name' => 'MIT',
        'url' => 'http://codemirror.net/LICENSE',
        'gpl-compatible' => TRUE,
      ],
    ];

    $assets = [
      'js' => [
        'lib/codemirror.js',
        'addon/edit/closetag.js',
        'addon/fold/foldcode.js',
        'addon/fold/foldgutter.js',
        'addon/fold/brace-fold.js',
        'addon/fold/xml-fold.js',
        'addon/fold/comment-fold.js',
        'addon/display/autorefresh.js',
        'addon/display/fullscreen.js',
        'addon/display/placeholder.js',
        'addon/mode/overlay.js',
        'addon/comment/comment.js',
        'addon/selection/active-line.js',
      ],
      'css' => [
        'lib/codemirror.css',
        'addon/fold/foldgutter.css',
        'addon/display/fullscreen.css',
      ],
    ];

    foreach ($this->modeManager->getActiveModes() as $mode) {
      $assets['js'][] = "mode/$mode/$mode.js";
    }

    // hook_library_info_alter() is not quite convenient here because the
    // implementors have to take care about CDN option.
    $this->moduleHandler->alter('codemirror_editor_assets', $assets);

    // BC Layer. Before 8.x-1.1 files were always minified. Modules implementing
    // hook_codemirror_editor_assets_alter() may still declare minified files.
    foreach (['js', 'css'] as $type) {
      foreach ($assets[$type] as $index => $asset) {
        $assets[$type][$index] = preg_replace("#\.min(\.$type)$#i", '\1', $asset);
      }
    }

    if ($settings['cdn']) {
      $prefix = str_replace('{version}', static::CODEMIRROR_VERSION, static::CDN_URL) . '/';
      $options = ['type' => 'external'];
    }
    else {
      $prefix = static::LIBRARY_PATH;
      $options = [];
    }

    if ($settings['minified']) {
      $options['minified'] = TRUE;
      // Add '.min' suffix to all file names.
      foreach (['js', 'css'] as $type) {
        foreach ($assets[$type] as $index => $asset) {
          $assets[$type][$index] = preg_replace("#(\.$type)$#i", '.min\1', $asset);
        }
      }
    }

    foreach ($assets['js'] as $file) {
      $library['js'][$prefix . $file] = $options;
    }

    foreach ($assets['css'] as $file) {
      $library['css']['component'][$prefix . $file] = $options;
    }

    if ($settings['theme'] != 'default') {
      $file_name = $prefix . 'theme/' . $settings['theme'] . ($settings['minified'] ? '.min' : '') . '.css';
      $library['css']['theme'][$file_name] = $options;
    }

    return $library;
  }

}
