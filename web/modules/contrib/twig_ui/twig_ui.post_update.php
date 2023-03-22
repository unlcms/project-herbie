<?php

/**
 * @file
 * Post update functions for Twig UI Templates.
 */

/**
 * Clear cache due to change in template_manager service.
 *
 * Added messenger service as constructor parameter.
 */
function twig_ui_post_update_template_manager_service_add_messenger() {
  // Empty post-update function.
}

/**
 * Clear cache due to new 'twig_ui.immutable_registry' service.
 */
function twig_ui_post_update_add_immutable_registry() {
  // Empty post-update function.
}

/**
 * Clear cache due to updated 'twig_ui.immutable_registry' service.
 */
function twig_ui_post_update_update_immutable_registry() {
  // Empty post-update function.
}
