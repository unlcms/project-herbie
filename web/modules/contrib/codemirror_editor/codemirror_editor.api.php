<?php

/**
 * @file
 * Hooks provided by the CodeMirror editor module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the list of CodeMirror language modes.
 *
 * @param array[] $modes
 *   A list of available language modes.
 *
 * @see codemirror_editor.codemirror_modes.yml
 */
function hook_codemirror_mode_info_alter(array &$modes) {
  // Make sure PHP language mode is always loaded.
  $modes['php']['usage'][] = 'my_module';
}

/**
 * Alters the list of CodeMirror assets.
 *
 * For requiring language modes hook_codemirror_mode_info_alter() is preferable.
 *
 * @param array[] $assets
 *   A list of asset paths relative to libraries/codemirror directory.
 */
function hook_codemirror_editor_assets_alter(array &$assets) {
  $assets['js'][] = 'addon/dialog/dialog.js';
  $assets['css'][] = 'addon/dialog/dialog.css';
}

/**
 * @} End of "addtogroup hooks".
 */
