<?php

/**
 * @file
 * Post update functions for Webform module.
 */

use Drupal\webform\Element\WebformHtmlEditor;

// Webform install helper functions.
include_once __DIR__ . '/includes/webform.install.inc';

// Webform update hooks.
include_once __DIR__ . '/includes/webform.install.update.inc';

/**
 * #3254570: Move jQuery UI datepicker support into dedicated deprecated module.
 */
function webform_post_update_deprecate_jquery_ui_datepicker() {
  if (!\Drupal::moduleHandler()->moduleExists('jquery_ui_datepicker')) {
    return;
  }

  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, 'datepicker') !== FALSE) {
      // Enable the webform_jqueryui_datepicker.module.
      \Drupal::service('module_installer')
        ->install(['webform_jqueryui_datepicker']);
      return;
    }
  }
}

/**
 * Issue #3247475: Location field Algolia Places sunsetting May 31, 2022.
 */
function webform_post_update_deprecate_location_places() {
  $config_factory = \Drupal::configFactory();
  $install_webform_location_places = FALSE;
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->get($webform_config_name);
    $elements = $webform_config->get('elements');
    if (strpos($elements, 'webform_location_places') !== FALSE) {
      $install_webform_location_places = TRUE;
      break;
    }
  }

  // Load webform.settings configuration.
  $config = \Drupal::configFactory()->getEditable('webform.settings');

  // Install and configure the webform_location_places.module.
  if ($install_webform_location_places) {
    // Install the webform_location_places.module.
    \Drupal::service('module_installer')
      ->install(['webform_location_places']);

    // Move the default APIs key.
    $app_id = $config->get('element.default_algolia_places_app_id');
    if ($app_id) {
      $config->set('third_party_settings.webform_location_places.default_algolia_places_app_id', $app_id);
    }
    $api_key = $config->get('element.default_algolia_places_api_key');
    if ($api_key) {
      $config->set('third_party_settings.webform_location_places.default_algolia_places_api_key', $api_key);
    }
  }
  else {
    // Remove 'webform_location_places' from excluded elements.
    $config->clear('element.excluded_elements.webform_location_places');
  }

  // Remove 'element.default_algolia_places_app_*'.
  $config->clear('element.default_algolia_places_app_id');
  $config->clear('element.default_algolia_places_api_key');

  // Save webform.settings configuration.
  $config->save();
}

/**
 * Move from custom CKEditor to hidden 'webform_default' text format.
 */
function webform_post_update_ckeditor() {
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  if (empty($config->get('html_editor.element_format'))) {
    $config->set('html_editor.element_format', WebformHtmlEditor::DEFAULT_FILTER_FORMAT);
  }
  if (empty($config->get('html_editor.mail_format'))) {
    $config->set('html_editor.mail_format', WebformHtmlEditor::DEFAULT_FILTER_FORMAT);
  }
  $config->save();

  _webform_update_html_editor();
}
