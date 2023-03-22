/**
 * @file
 * JS for the feeds admin UI.
 */

/**
 * Sets URL hash so that the correct settings tab is open on the feed type form.
 *
 * @see \Drupal\feeds\FeedTypeForm::ajaxCallback
 */
Drupal.AjaxCommands.prototype.feedsHash = function (ajax, response, status) {

  "use strict";

  window.location.hash = response.hash;
};
