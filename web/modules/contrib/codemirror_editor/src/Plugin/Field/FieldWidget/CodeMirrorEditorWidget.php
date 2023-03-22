<?php

namespace Drupal\codemirror_editor\Plugin\Field\FieldWidget;

use Drupal\codemirror_editor\CodeMirrorPluginTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'codemirror_editor' field widget.
 *
 * @FieldWidget(
 *   id = "codemirror_editor",
 *   label = @Translation("CodeMirror"),
 *   field_types = {
 *     "string_long",
 *     "text_long"
 *   },
 * )
 */
class CodeMirrorEditorWidget extends WidgetBase {

  use CodeMirrorPluginTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'rows' => '5',
      'placeholder' => '',
      'mode' => 'text/html',
    ];
    return $settings + self::getDefaultCodeMirrorSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Rows'),
      '#default_value' => $this->getSetting('rows'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered.'),
    ];

    return $element + self::buildCodeMirrorSettingsForm($this->getSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary[] = $this->t('Number of rows: @rows', ['@rows' => $settings['rows']]);
    if ($settings['placeholder'] != '') {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $settings['placeholder']]);
    }

    $summary[] = $this->t('Language mode: @mode', ['@mode' => $this->getSetting('mode')]);
    $summary[] = $this->t('Load toolbar: @toolbar', ['@toolbar' => $this->formatBoolean('toolbar')]);
    if ($settings['toolbar']) {
      $summary[] = $this->t('Toolbar buttons: @buttons', ['@buttons' => implode(', ', $settings['buttons'])]);
    }

    $summary[] = $this->t('Line wrapping: @lineWrapping', ['@lineWrapping' => $this->formatBoolean('lineWrapping')]);
    $summary[] = $this->t('Line numbers: @lineNumbers', ['@lineNumbers' => $this->formatBoolean('lineNumbers')]);
    $summary[] = $this->t('Fold gutter: @foldGutter', ['@foldGutter' => $this->formatBoolean('foldGutter')]);
    $summary[] = $this->t('Auto close tags: @autoCloseTags', ['@autoCloseTags' => $this->formatBoolean('autoCloseTags')]);
    $summary[] = $this->t('Style active line: @styleActiveLine', ['@styleActiveLine' => $this->formatBoolean('styleActiveLine')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $settings['mode'] = self::normalizeMode($settings['mode']);

    $element['value'] = $element + [
      '#type' => 'codemirror',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#rows' => $settings['rows'],
      '#placeholder' => $settings['placeholder'],
    ];

    // These options are not relevant to CodeMirror.
    unset($settings['rows'], $settings['placeholder']);
    $element['value']['#codemirror'] = $settings;

    return $element;
  }

}
