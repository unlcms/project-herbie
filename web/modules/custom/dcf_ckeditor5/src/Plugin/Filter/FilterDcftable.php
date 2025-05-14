<?php
/**
 * @file
 * Contains Drupal\dcf_ckeditor5\Plugin\Filter\FilterDcftable
 */

namespace Drupal\dcf_ckeditor5\Plugin\Filter;
use Drupal\Component\Utility\Html;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to add dcf classes to HTML elements.
 *
 * @Filter(
 *   id = "filter_dcfckeditor5",
 *   title = @Translation("dcf_ckeditor5"),
 *   description = @Translation("Filter data in ckeditor5's text area"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterDcftable extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    // Add dcf-table classes to tables
    if($this->settings['dcftable']) {
      foreach ($xpath->query('//table') as $node) {
        $classes = $node->getAttribute('class');
        $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
        $classes[] = 'dcf-table dcf-table-bordered';
        $node->setAttribute('class', implode(' ', $classes));
      }
    }
    // Add dcf-blockquote classes to blockquote
    if($this->settings['dcfblockquote']) {
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
    $form['dcftable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add dcf table classes'),
      '#default_value' => $this->settings['dcftable'],
      '#description' => $this->t('Adds dcf-tables and dcf-table-bordered classes to tables'),
    );

    $form['dcfblockquote'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add dcf blockquote classes'),
      '#default_value' => $this->settings['dcfblockquote'],
      '#description' => $this->t('Adds dcf-blockquote classe to blockquote'),
    );
    return $form;
  }
}

