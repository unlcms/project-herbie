<?php

/**
 * @file
 * Post update functions for Feeds Log.
 */

use Drupal\views\Entity\View;

/**
 * Sets an access handler for the 'feeds_import_logs' view.
 */
function feeds_log_post_update_set_feeds_import_logs_access() {
  $view = View::load('feeds_import_logs');
  $display = &$view->getDisplay('default');
  $display['display_options']['access']['type'] = 'feeds_log_access';
  $view->save();
}
