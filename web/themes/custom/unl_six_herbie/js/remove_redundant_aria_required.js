/**
 * @file
 * Remove redundant aria-required="true" from div wrappers (js-form-wrapper) causing accessibility (webaudit) issues. Mainly on webforms, but could be on other forms as well.
 */

(function (Drupal, once) {
  'use strict';
  Drupal.behaviors.removeRedundantAriaRequired = {
    attach: function (context) {
      // Some delay so Drupal.states can finish applying attributes
      setTimeout(function () {
        // Target divs that have both required and aria-required="true"
        const selector = 'div.js-form-wrapper[required="required"][aria-required="true"], ' +
                         'div.js-form-wrapper[required][aria-required="true"]';

        const wrappers = once('remove-aria-required-div', selector, context);

        wrappers.forEach(function (div) {
          div.removeAttribute('aria-required');
        });

      }, 200);
    }
  };
})(Drupal, once);
