<?php

namespace Drupal\feeds\Feeds\CustomSource;

use Drupal\Core\Form\FormStateInterface;

/**
 * A CSV source.
 *
 * @FeedsCustomSource(
 *   id = "csv",
 *   title = @Translation("CSV column"),
 * )
 */
class CsvSource extends BlankSource {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Add description.
    $form['value']['#description'] = $this->configSourceDescription();
    return $form;
  }

  /**
   * Returns the description for a single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if there's a description. Null otherwise.
   */
  protected function configSourceDescription() {
    if ($this->feedType->getParser()->getConfiguration('no_headers')) {
      return $this->t('Enter which column number of the CSV file to use: 0, 1, 2, etc.');
    }
    return $this->t('Enter the exact CSV column name. This is case-sensitive.');
  }

}
