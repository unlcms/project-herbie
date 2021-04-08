/**
 * @file
 * Initiates the WDN Events Band.
 */

(function ($, Drupal) {
  Drupal.behaviors.wdnEventsBand = {
    attach: function attach(context, settings) {
      // Check if WDN object is defined.
      if ('undefined' !== typeof WDN) {
        $('#events-band', context).once('band_initiated').each(function () {
          initEventsBand();
        });
      }
      // If WDN object isn't defined, then wait for inlineJSReady event.
      else {
        window.addEventListener('inlineJSReady', function () {
          initEventsBand();
        }, false);
      }
    }
  };

  function initEventsBand() {
    var json = $("script[data-selector-json=wdn-events-band-settings]")['0'].innerHTML;
    var json = JSON.parse(json);
    WDN.setPluginParam('events', 'href', json.url);
    WDN.initializePlugin('events-band', {limit: json.limit, rooms: true });
  }

})(jQuery, Drupal);
