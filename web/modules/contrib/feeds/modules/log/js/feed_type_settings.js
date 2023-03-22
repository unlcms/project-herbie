/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($, Drupal) {
  Drupal.behaviors.feedsLogSetSummary = {
    attach(context) {
      let $context = $(context);

      $context.find('#edit-log-configuration').drupalSetSummary((context) => {
        let enabled = $(context).find('input[name="log_configuration[status]"]:checked').val();
        if (enabled == 1) {
          return Drupal.t('Logging enabled');
        }
        else {
          return Drupal.t('Logging disabled');
        }
      });
    }
  };

})(jQuery, Drupal);
