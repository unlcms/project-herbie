<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * A base class for date targets.
 */
abstract class DateTargetBase extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * Prepares a date value.
   *
   * @param string $value
   *   The value to convert to a date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   A datetime object or null, if there is no value or if the date value
   *   has errors.
   */
  protected function convertToDate($value) {
    $value = trim((string) $value);

    // This is a year value.
    if (ctype_digit($value) && strlen($value) === 4) {
      $value = 'January ' . $value;
    }

    if (is_numeric($value)) {
      $date = DrupalDateTime::createFromTimestamp($value, $this->getTimezoneConfiguration());
    }
    elseif (strtotime($value)) {
      $date = new DrupalDateTime($value, $this->getTimezoneConfiguration());
    }

    if (isset($date) && !$date->hasErrors()) {
      return $date;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + ['timezone' => 'UTC'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone handling'),
      '#options' => $this->getTimezoneOptions(),
      '#default_value' => $this->configuration['timezone'],
      '#description' => $this->t('This value will only be used if the timezone is missing.'),
    ];

    return $form;
  }

  /**
   * Returns the timezone options.
   *
   * @return array
   *   A map of timezone options.
   */
  public function getTimezoneOptions() {
    return [
      '__SITE__' => $this->t('Site default'),
    ] + system_time_zones();
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    $options = $this->getTimezoneOptions();

    $summary[] = $this->t('Default timezone: %zone', [
      '%zone' => $options[$this->configuration['timezone']],
    ]);

    return $summary;
  }

  /**
   * Returns the timezone configuration.
   */
  public function getTimezoneConfiguration() {
    return ($this->configuration['timezone'] == '__SITE__') ?
      \Drupal::config('system.date')->get('timezone.default') : $this->configuration['timezone'];
  }

}
