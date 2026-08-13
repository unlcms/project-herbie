(function (Drupal) {
    // Messages are floated at the top of the page with .dcf-notice-overlay.
    // Need to ensure that the page scrolls all the way to the top when
    // a webform submission fails and displays a validation message.
    Drupal.webform = Drupal.webform || {};
    Drupal.webform.ajax = Drupal.webform.ajax || {};
    Drupal.webform.ajax.scrollTopOffset = 10000;
})(Drupal);
