/**
 * @file
 * Provides JS for the Twig UI settings form.
 */

(function (Drupal, $, once) {
  Drupal.behaviors.twigUiSettingsForm = {
    attach: function (context, settings) {
      $(document).ready(function () {
        toggleAllowedThemes()

        $(once('allowedThemesChange', "input[name=allowed_themes]")).change(function (e) {
          toggleAllowedThemes()
        });

        $(once('allowedThemeListItemChange', "input[name^=allowed_theme_list")).change(function (e) {
          showHideDefaultSelectedElement(e.target);
        });
      });

      /**
       * Show/hide form elements based on "allowed themes" radio value.
       */
      function toggleAllowedThemes() {
        var allowedThemes = $("input[name=allowed_themes]:checked").val();
        if (allowedThemes == 'selected') {
          showHideAllDefaultSelectedElements();
        }
        else if (allowedThemes == 'all') {
          showHideAllDefaultSelectedElements(true);
        }
      }

      /**
       * Show/hide all "default selected themes" elements.
       *
       * @param {boolean} force
       *   Defer to allowed theme list if null.
       *   Force to show if true.
       *   Force to hide if false.
       */
      function showHideAllDefaultSelectedElements(force = null) {
        $("input[name^=allowed_theme_list", context).each(function (e) {
          showHideDefaultSelectedElement(this, force);
        });
      }

      /**
       * Show/hide a given "default selected themes" element.
       *
       * @param {object} element
       *   The DOM element of the "allowed themes list" checkbox.
       * @param {boolean} force
       *   Defer to allowed theme list if null.
       *   Force to show if true.
       *   Force to hide if false.
       */
      function showHideDefaultSelectedElement(element, force = null) {
        var value = element.value.replace(/_/g, "-");

        if (force == null) {
          if (element.checked) {
            $(".form-item-default-selected-themes-" + value).show();
          }
          else if (!element.checked) {
            $(".form-item-default-selected-themes-" + value).hide();
          }
        }
        else if (force = true) {
          $(".form-item-default-selected-themes-" + value).show();
        }
        else if (force = false) {
          $(".form-item-default-selected-themes-" + value).hide();
        }

      }
    }
  };
})(Drupal, jQuery, once);
