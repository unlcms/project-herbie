window.addEventListener('inlineJSReady', function() {
    WDN.initializePlugin('tabs');
}, false);
window.addEventListener('inlineJSReady', function() {

    if (define.amd === undefined) {
       define.amd = define.origAmd;
       delete define.origAmd;
    }
    window.WDNPluginsExecuting++;
 
    WDN.initializePlugin(
       'tabs',
       {},
       tabsCallback,
       'after'
    );
 
    function tabsCallback() {
       window.WDNPluginsExecuting--;
       if (define.origAmd === undefined && window.WDNPluginsExecuting === 0) {
          define.origAmd = define.amd;
          delete define.amd;
       }
    }
 
 }, false);