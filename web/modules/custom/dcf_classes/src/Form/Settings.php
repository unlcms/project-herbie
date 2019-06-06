<?php

namespace Drupal\dcf_classes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to set available DCF classes.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dcf_classes_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dcf_classes.classes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dcf_classes.classes');

    $form['heading'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Heading Classes'),
      '#description' => $this->t('One per line. Do not include a dot.'),
      '#default_value' => implode(PHP_EOL, $config->get('heading')),
      '#rows' => 15,
    ];

    $form['section'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Section Classes'),
      '#description' => $this->t('One per line. Do not include a dot.'),
      '#default_value' => implode(PHP_EOL, $config->get('section')),
      '#rows' => 15,
    ];

    $section_packages = $config->get('section_packages');
    $options = [];
    foreach ($section_packages as $key => $value) {
      $options[] = $key . '|' . $value;
    }
    $form['section_packages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Section Packages'),
      '#description' => $this->t('Add sets of classes using a format of Name|classes, with each definition on a new line and classes separated by a space.'),
      '#default_value' => implode(PHP_EOL, $options),
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $settings = $this->config('dcf_classes.classes');

    // Heading.
    $heading_array = preg_split("[\n|\r]", $values['heading']);
    foreach ($heading_array as $key => $class) {
      if (empty($class)) {
        unset($heading_array[$key]);
      }
    }
    $heading_array = array_filter(array_values($heading_array));
    $settings->set('heading', $heading_array);

    // Section.
    $section_array = preg_split("[\n|\r]", $values['section']);
    foreach ($section_array as $key => $class) {
      if (empty($class)) {
        unset($section_array[$key]);
      }
    }
    $section_array = array_filter(array_values($section_array));
    $settings->set('section', $section_array);

    // Section packages.
    $section_packages_array = preg_split("[\n|\r]", $values['section_packages']);
    $section_packages = [];
    foreach ($section_packages_array as $package) {
      if (!empty(trim($package))) {
        $pieces = array_map('trim', explode('|', $package));
        $section_packages[$pieces[0]] = $pieces[1];
      }
    }
    $settings->set('section_packages', $section_packages);

    $settings->save();
    parent::submitForm($form, $form_state);
  }

}
