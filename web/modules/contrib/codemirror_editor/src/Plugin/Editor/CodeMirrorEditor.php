<?php

namespace Drupal\codemirror_editor\Plugin\Editor;

use Drupal\codemirror_editor\CodeMirrorPluginTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorBase;

/**
 * Defines a CodeMirror text editor.
 *
 * @Editor(
 *   id = "codemirror_editor",
 *   label = @Translation("CodeMirror editor"),
 *   supports_content_filtering = FALSE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea",
 *   }
 * )
 */
class CodeMirrorEditor extends EditorBase {

  use CodeMirrorPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return self::getDefaultCodeMirrorSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return self::buildCodeMirrorSettingsForm($form_state->get('editor')->getSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function getJsSettings(Editor $editor) {
    return $editor->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return ['codemirror_editor/editor'];
  }

}
