<?php

namespace Drupal\codemirror_editor\Plugin\Filter;

use Drupal\codemirror_editor\CodeMirrorPluginTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a 'CodeMirror' filter.
 *
 * @Filter(
 *   id = "codemirror_editor",
 *   title = @Translation("CodeMirror"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "lineWrapping" = false,
 *     "lineNumbers" = true,
 *     "foldGutter" = false
 *   }
 * )
 */
class CodeMirrorEditor extends FilterBase {

  use CodeMirrorPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $keys = ['lineWrapping', 'lineNumbers', 'foldGutter'];
    return self::buildCodeMirrorSettingsForm($this->settings, $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    $options = $this->settings;
    $options['readOnly'] = TRUE;
    $options['toolbar'] = FALSE;

    $pattern = '#<code\s+?data-mode\s*?=\s*?"([a-z0-9_/\-]*?)"[^>]*?>(.*?)</\s*code\s*>#is';
    $processor = function ($matches) use ($options) {
      $options['mode'] = self::normalizeMode($matches[1]);
      $options = Html::escape(json_encode($options));
      $code = Html::escape($matches[2]);
      return '<code data-codemirror="' . $options . '">' . $code . '</code>';
    };
    $output = preg_replace_callback($pattern, $processor, $text, -1, $count);

    if ($count > 0) {
      $build['#attached']['library'][] = 'codemirror_editor/formatter';
      \Drupal::service('renderer')->render($build);
    }

    return new FilterProcessResult($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $tip_arguments = [
      '@expression' => '<code data-mode="mode">...</code>',
    ];
    return $this->t('Syntax highlight code surrounded by the <code>@expression</code> tags.', $tip_arguments);
  }

}
