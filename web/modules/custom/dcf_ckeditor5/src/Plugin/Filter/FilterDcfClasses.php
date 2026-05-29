<?php

namespace Drupal\dcf_ckeditor5\Plugin\Filter;

use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a filter to add dcf classes to HTML elements.
 */
#[Filter(
  id: "filter_dcfckeditor5",
  title: new TranslatableMarkup("DCF filters"),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  settings: [
    "dcftable" => TRUE,
    "dcfblockquote" => TRUE,
  ]
)]
class FilterDcfClasses extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    // Add dcf-table classes to tables.
    if ($this->settings['dcftable']) {
      foreach ($xpath->query('//table') as $node) {
        $classes = $node->getAttribute('class');
        $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
        $classes[] = 'dcf-table dcf-table-bordered';
        $node->setAttribute('class', implode(' ', $classes));
      }
    }

    // Add dcf-blockquote classes to blockquote.
    if ($this->settings['dcfblockquote']) {
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//blockquote') as $node) {
        $classes = $node->getAttribute('class');
        $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
        $classes[] = 'dcf-blockquote';
        $node->setAttribute('class', implode(' ', $classes));
      }
    }

    $result->setProcessedText(Html::serialize($dom));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['dcftable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add dcf table classes'),
      '#default_value' => $this->settings['dcftable'],
      '#description' => $this->t('Adds dcf-tables and dcf-table-bordered classes to tables'),
    ];
    $form['dcfblockquote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add dcf blockquote classes'),
      '#default_value' => $this->settings['dcfblockquote'],
      '#description' => $this->t('Adds dcf-blockquote classe to blockquote'),
    ];
    return $form;
  }
}

