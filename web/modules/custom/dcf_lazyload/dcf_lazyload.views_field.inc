<?php

/**
 * @file
 * This module provides DCF Lazy Loading integration for views fields.
 *
 * Because third-party settings are not available to view fields, we're
 * hijaking the responsive image schema to add our own values. To override
 * the schema, we're setting this module's weight to 1. Once
 * https://www.drupal.org/project/drupal/issues/2686145 is committed, we can
 * switch to third-party settings. This was done to avoid creating a separate
 * formatter.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Targets "views_ui_config_item_form" form to add options to responsive
 * image formatter in Views.
 */
function dcf_lazyload_form_views_ui_config_item_form_alter(&$form, FormStateInterface &$form_state, $form_id) {
  // Check that the responsive image style form element is loaded.
  if (isset($form['options']['settings']['responsive_image_style'])) {
    $form_state_storage = $form_state->getStorage();
    $display_id = $form_state_storage['display_id'];
    $field_name = $form_state_storage['id'];
    /* @var Drupal\views_ui\ViewUI */
    $view = $form_state_storage['view'];
    $display = $view->getDisplay($display_id);
    $changed_display = $display['display_options'];

    // Check if field settings are set on changed display.
    if (isset($changed_display['display_options']['fields'][$field_name]['settings'])) {
      $settings = $changed_display['display_options']['fields'][$field_name]['settings'];
    }
    // Otherwise, load the master display and check there.
    else {
      $master_display = $view->getDisplay('default');
      if (isset($master_display['display_options']['fields'][$field_name]['settings'])) {
        $settings = $master_display['display_options']['fields'][$field_name]['settings'];
      }
      // If there are no settings, then use an empty array.
      else {
        $settings = [];
      }
    }

    $form['options']['settings']['responsive_image_style']['#weight'] = -10;

    $form['options']['settings']['dcf_lazyload_enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable DCF Lazy Loading'),
      '#default_value' => (isset($settings['dcf_lazyload_enable'])) ? $settings['dcf_lazyload_enable'] : FALSE,
      '#value_callback' => '_dcf_lazyload_checkbox_boolean',
      '#weight' => -9,
    ];
  }
}

/**
 * Implements template_preprocess_views_view_field().
 *
 * This is the entry point for image fields rendered by Views.
 */
function dcf_lazyload_preprocess_views_view_field(&$variables) {
  // Loop through fields in the view.
  foreach ($variables["view"]->field as $field_name => $field) {
    // Check if field type is responsive image.
    if (isset($field->options['type']) && $field->options['type'] == 'responsive_image') {
      // Get views field settings.
      $settings = $variables["view"]->field[$field_name]->options["settings"];
      if (isset($settings['dcf_lazyload_enable'])) {
        // Get field value array if the field exists and is populated.
        if($field_value = $variables['row']->_entity->__get($field_name)) {
          $field_value = $variables['row']->_entity->__get($field_name)->getValue();

          // Loop through field value (fields can be multi-value).
          foreach ($field_value as $key => $item) {
            $field_value[$key]['dcf_lazyload_enable'] = (isset($settings['dcf_lazyload_enable'])) ? $settings['dcf_lazyload_enable'] : FALSE;
          }
          // Overwrite field value array with updated value.
          // Attributes will be added by
          // dcf_lazyload_preprocess_responsive_image_formatter().
          $variables['row']->_entity->__set($field_name, $field_value);
        }
      }
    }
  }
}
