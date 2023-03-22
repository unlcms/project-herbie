<?php

namespace Drupal\feeds\Feeds\CustomSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\CustomSource\CustomSourceBase;

/**
 * A custom source.
 *
 * @FeedsCustomSource(
 *   id = "blank",
 *   title = @Translation("Blank"),
 * )
 */
class BlankSource extends CustomSourceBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'value' => '',
      'machine_name' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->configuration['machine_name']) {
      // Show label field only when editing custom source.
      $form['label'] = [
        '#title' => $this->t('Label'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['label'],
      ];

      $form['value'] = [
        '#title' => $this->t('Value'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['value'],
      ];

      $form['machine_name'] = [
        '#type' => 'value',
        '#value' => $this->configuration['machine_name'],
      ];
    }
    else {
      $form['value'] = [
        '#type' => 'textfield',
        '#default_value' => $this->configuration['value'],
        '#weight' => -2,
      ];
    }

    $form['value']['#description'] = $this->configSourceDescription();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsParserType($parser_type) {
    // All parsers are supported by this custom source type.
    return TRUE;
  }

  /**
   * Returns the description for a single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if there's a description. Null otherwise.
   */
  protected function configSourceDescription() {
    return $this->t('Ignored by parsers. Use this as a placeholder to either give it a value programmatically or set a value on it with Feeds Tamper.');
  }

}
