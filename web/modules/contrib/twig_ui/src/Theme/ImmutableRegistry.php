<?php

namespace Drupal\twig_ui\Theme;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Theme\Registry as CoreRegistry;
use Drupal\Core\Theme\ThemeInitializationInterface;
use PharIo\Version\Version;

/**
 * An extended Registry class for retrieving the unmodified registry.
 *
 * Twig UI can retrieve template code from the file system for a given theme
 * (including inherited templates). In order to do so, it needs to be able to
 * 1) set the theme for the registry build and 2) bypass the
 * theme_registry:[theme] cache.
 *
 * It's not possible to decorate the theme.registry service.
 * See https://www.drupal.org/project/drupal/issues/3155536.
 */
class ImmutableRegistry extends CoreRegistry {

  /**
   * Constructs a \Drupal\Core\Theme\Registry object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend interface to use for the complete theme registry data.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use to load modules.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   * @param \Drupal\Core\Cache\CacheBackendInterface $runtime_cache
   *   The cache backend interface to use for the runtime theme registry data.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module list.
   * @param string $theme_name
   *   (optional) The name of the theme for which to construct the registry.
   */
  public function __construct($root, CacheBackendInterface $cache, LockBackendInterface $lock, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ThemeInitializationInterface $theme_initialization, CacheBackendInterface $runtime_cache, ModuleExtensionList $module_list, $theme_name = NULL) {
    // In Drupal 9.5.0, the constructor changed.
    // Drupal 9.5.0+ constructor.
    $min_required_version = new Version('9.5.0-dev');
    $core_version = new Version(\Drupal::VERSION);
    if ($core_version->isGreaterThan($min_required_version) ||
      $core_version->equals($min_required_version)
      ) {
      parent::__construct($root, $cache, $lock, $module_handler, $theme_handler, $theme_initialization, $runtime_cache, $module_list, $theme_name);
    }
    // Drupal pre-9.5.0 constructor.
    else {
      parent::__construct($root, $cache, $lock, $module_handler, $theme_handler, $theme_initialization, $theme_name, $runtime_cache, $module_list);
    }
  }

  /**
   * Get the theme for this instance of Registry.
   *
   * @return string
   *   The machine name of the theme.
   */
  public function getTheme() {
    return $this->theme->getName();
  }

  /**
   * Set the theme for this instance of Registry.
   *
   * @param string $theme_name
   *   The machine name of the theme.
   */
  public function setTheme($theme_name) {
    $this->themeName = $theme_name;
  }

  /**
   * Returns the complete theme registry by rebuilding it.
   *
   * This method's code is identical to the parent's method with the exception
   * that cached values are stored in twig_ui.theme_registry:[theme] instead of
   * theme_registry:[theme].
   *
   * @return array
   *   The complete theme registry data array.
   *
   * @see Registry::$registry
   */
  public function get() {
    $this->init($this->themeName);
    if (isset($this->registry[$this->theme->getName()])) {
      return $this->registry[$this->theme->getName()];
    }
    if ($cache = $this->cache->get('twig_ui.theme_registry:' . $this->theme->getName())) {
      $this->registry[$this->theme->getName()] = $cache->data;
    }
    else {
      $this->build();
      // Only persist it if all modules are loaded to ensure it is complete.
      if ($this->moduleHandler->isLoaded()) {
        $this->setCache();
      }
    }
    return $this->registry[$this->theme->getName()];
  }

  /**
   * {@inheritdoc}
   *
   * This method's code is identical to the parent's method with the exception
   * that cached values are stored in twig_ui.theme_registry:[theme] instead of
   * theme_registry:[theme].
   */
  protected function setCache() {
    $this->cache->set('twig_ui.theme_registry:' . $this->theme->getName(), $this->registry[$this->theme->getName()], Cache::PERMANENT, ['theme_registry']);
  }

}
