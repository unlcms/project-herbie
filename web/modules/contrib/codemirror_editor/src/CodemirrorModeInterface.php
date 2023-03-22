<?php

namespace Drupal\codemirror_editor;

/**
 * Interface for CodeMirror mode plugins.
 */
interface CodemirrorModeInterface {

  /**
   * Returns the plugin label.
   *
   * @return string
   *   The label of the language mode.
   */
  public function label();

}
