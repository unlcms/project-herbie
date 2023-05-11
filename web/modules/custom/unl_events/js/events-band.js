/**
 * @file
 * Initiates the WDN Events Band.
 */

(function (Drupal, $, once) {
  Drupal.behaviors.wdnEventsBand = {
    attach: function attach(context, settings) {
      // Check if WDN object is defined.
      if ('undefined' !== typeof WDN) {
        const elements = once('band_initiated', '#events-band', context);
        elements.forEach(initEventsBand);
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
    if (define.amd === undefined) {
      define.amd = define.origAmd;
      delete define.origAmd;
    }
    window.WDNPluginsExecuting++;

    var json = $("script[data-selector-json=wdn-events-band-settings]")['0'].innerHTML;
    var json = JSON.parse(json);
    WDN.setPluginParam('events', 'href', json.url);
    WDN.initializePlugin(
        'events-band',
        {limit: json.limit, rooms: true},
        enableAMD,
        'after'
    );

    function enableAMD() {
      window.WDNPluginsExecuting--;
      if (define.origAmd === undefined && window.WDNPluginsExecuting === 0) {
        define.origAmd = define.amd;
        delete define.amd;
      }
    }
  }

})(Drupal, jQuery, once);
