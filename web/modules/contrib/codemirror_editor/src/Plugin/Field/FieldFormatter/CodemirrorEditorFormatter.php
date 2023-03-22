<?php

namespace Drupal\codemirror_editor\Plugin\Field\FieldFormatter;

use Drupal\codemirror_editor\CodeMirrorPluginTrait;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Codemirror' formatter.
 *
 * @FieldFormatter(
 *   id = "codemirror_editor",
 *   label = @Translation("Codemirror"),
 *   field_types = {
 *     "string_long",
 *     "text_long"
 *   }
 * )
 */
class CodemirrorEditorFormatter extends FormatterBase {

  use CodeMirrorPluginTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'mode' => 'text/html',
      'lineWrapping' => FALSE,
      'lineNumbers' => TRUE,
      'foldGutter' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $keys = ['mode', 'lineWrapping', 'lineNumbers', 'foldGutter'];
    return self::buildCodeMirrorSettingsForm($this->getSettings(), $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Language mode: @mode', ['@mode' => $this->getSetting('mode')]);
    $summary[] = $this->t('Line wrapping: @lineWrapping', ['@lineWrapping' => $this->formatBoolean('lineWrapping')]);
    $summary[] = $this->t('Line numbers: @lineNumbers', ['@lineNumbers' => $this->formatBoolean('lineNumbers')]);
    $summary[] = $this->t('Fold gutter: @foldGutter', ['@foldGutter' => $this->formatBoolean('foldGutter')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $settings = $this->getSettings();
    $settings['mode'] = self::normalizeMode($settings['mode']);
    $settings['readOnly'] = TRUE;
    $settings['toolbar'] = FALSE;

    foreach ($items as $delta => $item) {
      $element[$delta]['#markup'] = new FormattableMarkup(
        '<code data-codemirror="@codemirror" class="cme-code">@value</code>',
        [
          '@codemirror' => json_encode($settings),
          '@value' => "\n$item->value\n",
        ]
      );
    }

    $element['#attached']['library'][] = 'codemirror_editor/formatter';
    return $element;
  }

}
