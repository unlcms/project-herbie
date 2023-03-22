<?php

namespace Drupal\codemirror_editor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Defines a plugin manager to deal with CodeMirror modes.
 *
 * Modules can define CodeMirror modes in a MODULE_NAME.codemirror_modes.yml
 * file located in the module's base directory. Each CodeMirror mode has the
 * following structure:
 *
 * @code
 *   MACHINE_NAME:
 *     label: STRING
 *     mime_types:
 *       - STRING
 *       - STRING
 *     usage: ARRAY (OPTIONAL)
 *     dependencies: ARRAY (OPTIONAL)
 * @endcode
 *
 * @see codemirror_editor.codemirror_modes.yml
 */
class CodemirrorModePluginManager extends DefaultPluginManager implements CodemirrorModeManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'usage' => [],
    'dependencies' => [],
    'class' => 'Drupal\codemirror_editor\CodemirrorModeDefault',
  ];

  /**
   * Constructs CodemirrorModePluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->alterInfo('codemirror_mode_info');
    $this->setCacheBackend($cache_backend, 'codemirror_mode_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('codemirror_modes', $this->moduleHandler->getModuleDirectories());
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveModes() {

    $enabled_modes = $this->configFactory
      ->get('codemirror_editor.settings')
      ->get('language_modes');

    $modes = [];
    foreach ($this->getDefinitions() as $mode => $definition) {
      if (in_array($mode, $enabled_modes) || count($definition['usage']) > 0) {
        $modes[] = $mode;
        foreach ($definition['dependencies'] as $dependency) {
          $modes[] = $dependency;
        }
      }
    }

    return array_unique($modes);
  }

  /**
   * {@inheritdoc}
   */
  public function normalizeMode($mode) {
    $mode = strtolower($mode);
    if (strpos($mode, '/') === FALSE) {
      // HTML is actually just a subtype of XML.
      if ($mode == 'html') {
        $mode = 'text/html';
      }
      else {
        $modes = $this->getDefinitions();
        if (isset($modes[$mode])) {
          // Consider the first declared mime type as a default one.
          // @see codemirror_editor.language_modes.yml
          $mode = reset($modes[$mode]['mime_types']);
        }
      }
    }
    return $mode;
  }

}
