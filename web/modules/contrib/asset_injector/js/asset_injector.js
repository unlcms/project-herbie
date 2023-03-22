(function ($, window, Drupal) {
  'use strict';

  Drupal.behaviors.assetInjectorSettingsSummary = {
    attach: function attach() {
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      function selectSummary(context) {
        var vals = [];
        var $select = $(context).find('select');

        if ($($select).attr('multiple')) {
          $.each($($select).val(), function (i, e) {
            vals.push($($select).find('option[value="' + e + '"]').html());
          });
        }
        else {
          vals.push($($select).find('option[value="' + $($select).val() + '"]').html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }


      function checkboxesSummary(context) {
        var vals = [];
        var $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
        var il = $checkboxes.length;
        for (var i = 0; i < il; i++) {
          vals.push($($checkboxes[i]).html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('[data-drupal-selector="edit-conditions-node-type"], [data-drupal-selector="edit-conditions-language"], [data-drupal-selector="edit-conditions-user-role"]').drupalSetSummary(checkboxesSummary);
      $('[data-drupal-selector="edit-conditions-current-theme"]').drupalSetSummary(selectSummary);

      $('[data-drupal-selector="edit-conditions-and-or"]').drupalSetSummary(function (context) {
        var require_all = $(context).find('input[type="checkbox"]:checked ');

        if (require_all.length) {
          return Drupal.t('Require ALL conditions');
        }
        return Drupal.t('Require any condition');
      });

      $('[data-drupal-selector="edit-conditions-request-path"]').drupalSetSummary(function (context) {
        var $pages = $(context).find('textarea[name="conditions[request_path][pages]"]');
        if (!$pages.val()) {
          return Drupal.t('Not restricted');
        }

        return Drupal.t('Restricted to certain pages');
      });
    }
  };
})(jQuery, window, Drupal);
