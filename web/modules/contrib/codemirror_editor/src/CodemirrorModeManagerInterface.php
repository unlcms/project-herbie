<?php

namespace Drupal\codemirror_editor;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for CodeMirror mode manager.
 */
interface CodemirrorModeManagerInterface extends PluginManagerInterface {

  /**
   * Returns active language modes.
   *
   * @return string[]
   *   An array of active language modes.
   */
  public function getActiveModes();

  /**
   * Normalizes language mode.
   *
   * @param string $mode
   *   Language mode to normalize.
   *
   * @return string
   *   Normalized language mode.
   */
  public function normalizeMode($mode);

}
