<?php

namespace Drupal\codemirror_editor;

use Drupal\Core\Plugin\PluginBase;

/**
 * Default class used for CodeMirror mode plugins.
 */
class CodemirrorModeDefault extends PluginBase implements CodemirrorModeInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

}
