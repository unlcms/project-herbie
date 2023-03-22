<?php

/**
 * @file
 * Hooks provided by the block_form_alter module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter block forms per block plugin.
 *
 * Block forms for the 'block_content' and 'inline_content' plugins must use
 * hook_block_type_form_alter().
 *
 * @param array $form
 *   Nested array of form elements that comprise the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param string $plugin
 *   The machine name of the plugin implementing the block.
 */
function hook_block_plugin_form_alter(array &$form, FormStateInterface &$form_state, string $plugin) {
  if ($plugin == 'webform_block') {
    $form['settings']['redirect']['#default_value'] = TRUE;
    $form['settings']['redirect']['#disabled'] = TRUE;
  }
}

/**
 * Alter custom block forms rendered by Block Content and Layout Builder.
 *
 * E.g. Alter block forms for 'block_content' and 'inline_block' plugins.
 *
 * @param array $form
 *   Nested array of form elements that comprise the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param string $block_type
 *   The machine name of the custom block bundle.
 */
function hook_block_type_form_alter(array &$form, FormStateInterface &$form_state, string $block_type) {
  if ($block_type == 'accordion') {
    $form['example_field']['widget'][0]['value']['#default_value'] = 'A better default value';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
