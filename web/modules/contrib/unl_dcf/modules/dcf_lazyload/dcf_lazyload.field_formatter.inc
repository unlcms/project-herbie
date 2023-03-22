<?php

/**
 * @file
 * This module provides DCF Lazy Loading integration for field formatters.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Implements hook_field_formatter_third_party_settings_form().
 */
function dcf_lazyload_field_formatter_third_party_settings_form($plugin, $field_definition, $view_mode, $form, $form_state) {
  if ($plugin->getPluginId() == 'responsive_image') {
    $element = [];
    $element['dcf_lazyload_enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable DCF Lazy Loading'),
      '#default_value' => $plugin->getThirdPartySetting('dcf_lazyload', 'dcf_lazyload_enable'),
      '#value_callback' => '_dcf_lazyload_checkbox_boolean',
    ];
    return $element;
  }
}

/**
 * Implements hook_field_formatter_settings_summary_alter().
 */
function dcf_lazyload_field_formatter_settings_summary_alter(&$summary, $context) {
  if ($context['formatter']->getPluginId() == 'responsive_image') {
    if ($context['formatter']->getThirdPartySetting('dcf_lazyload', 'dcf_lazyload_enable')) {
      $summary[] = t('DCF lazy loading is enabled.');
    }
  }
}

/**
 * Implements hook_preprocess_field().
 *
 * This is the entry point for image fields rendered Fields.
 */
function dcf_lazyload_preprocess_field(&$variables) {
  if ($variables['element']['#formatter'] == 'responsive_image') {
    $entity = $variables['element']['#object'];
    $view_mode = $variables['element']['#view_mode'];
    $field_name = $variables['element']['#field_name'];

    // Get field formatter settings.
    $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $field_display = $entity_display->getComponent($field_name);

    // Add properties given field formatter settings.
    // Attributes are added by
    // dcf_lazyload_preprocess_responsive_image_formatter().
    if (isset($field_display['third_party_settings']['dcf_lazyload']['dcf_lazyload_enable'])
      && $field_display['third_party_settings']['dcf_lazyload']['dcf_lazyload_enable'] == TRUE
      ) {
      foreach ($variables['items'] as $key => $item) {
        $variables['items'][$key]['content']['#item']->__set('dcf_lazyload_enable', TRUE);
      }
    }
  }
}
