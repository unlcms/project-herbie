<?php

namespace Drupal\codemirror_editor\Element;

use Drupal\Core\Render\Element\Textarea;

/**
 * Provides a form element for input text with code highlighting.
 *
 * Properties:
 * - #codemirror: array of CodeMirror options.
 * - All textarea options.
 *
 * Usage example:
 * @code
 * $form['code'] = [
 *   '#type' => 'codemirror',
 *   '#title' => $this->t('Text'),
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\TextArea
 *
 * @FormElement("codemirror")
 */
class CodeMirror extends Textarea {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#codemirror'] = [];
    $info['#pre_render'][] = [static::class, 'preRenderCodeMirror'];
    return $info;
  }

  /**
   * Enables CodeMirror editor for the element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderCodeMirror(array $element) {
    if (isset($element['#codemirror'])) {
      $element['#attributes']['data-codemirror'] = json_encode($element['#codemirror']);
      $element['#attached']['library'][] = 'codemirror_editor/editor';
    }
    return $element;
  }

}
