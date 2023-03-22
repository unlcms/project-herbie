<?php

namespace Drupal\dcf_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "DCF Table" plugin.
 *
 * @CKEditorPlugin(
 *   id = "dcf_table",
 *   label = @Translation("DCF Table")
 * )
 */
class DcfTable extends CKEditorPluginBase implements CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    $module_path = \Drupal::service('extension.list.module')->getPath('dcf_ckeditor');
    return $module_path . '/js/plugin/dcf_table/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['dcf_base']['enabled_plugins']['dcf_table'])
      && $settings['plugins']['dcf_base']['enabled_plugins']['dcf_table'] != '0'
      ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
