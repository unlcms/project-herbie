// window.addEventListener('inlineJSReady', function() {

//    if (define.amd === undefined) {
//       define.amd = define.origAmd;
//       delete define.origAmd;
//    }
//    window.WDNPluginsExecuting++;

//    WDN.initializePlugin(
//       'slideshows',
//       {},
//       slideshowCallback,
//       'after'
//    );

//    function slideshowCallback() {
//       window.WDNPluginsExecuting--;
//       if (define.origAmd === undefined && window.WDNPluginsExecuting === 0) {
//          define.origAmd = define.amd;
//          delete define.amd;
//       }
//    }

// }, false);
