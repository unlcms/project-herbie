<?php

namespace Drupal\twig_ui\Theme;

use Drupal\Core\Theme\Registry as CoreRegistry;

/**
 * An extended Registry class for replacement of core theme.registry service.
 *
 * When the Twig UI module is enabled, the core theme.registry service is
 * replaced by this class. This class registers Twig UI templates for
 * designated themes.
 *
 * It's not possible to decorate the theme.registry service.
 * See https://www.drupal.org/project/drupal/issues/3155536.
 */
class RegistryDecorator extends CoreRegistry {

  /**
   * {@inheritdoc}
   */
  protected function build() {
    $cache = parent::build();

    // twig_ui_theme() is unaware of the theme for which this registry is being
    // built, so it's passed via a global variable.
    global $_twig_ui_registry_theme;
    $_twig_ui_registry_theme = $this->theme->getName();

    // Process the twig_ui module. The module has already been processed once;
    // however, in order for its templates to be chosen in terms of theme
    // inheritance, it must be processed again after all themes have been
    // processed. ::processExtension() is quite opinionated and inflexible.
    $path = \Drupal::service('twig_ui.template_manager')->getDirectoryPathByTheme($_twig_ui_registry_theme, FALSE);
    $this->processExtension($cache, 'twig_ui', 'module', 'twig_ui', $path);

    // This is duplicative; however, duplication is unavoidable given how
    // parent::build() is coded.
    $this->postProcessExtension($cache, $this->theme);

    foreach ($cache as $hook => $info) {
      if (empty($info['preprocess functions'])) {
        unset($cache[$hook]['preprocess functions']);
      }
    }

    $this->registry[$this->theme->getName()] = $cache;

    return $cache;
  }

}
