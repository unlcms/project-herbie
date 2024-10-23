// Code block is commented until framework 6 update (removal of require.js)

// window.addEventListener('inlineJSReady', function () {
//     if (define.amd === undefined) {
//       define.amd = define.origAmd;
//       delete define.origAmd;
//     }
//     window.WDNPluginsExecuting++;

//     WDN.initializePlugin(
//       'scroll-animations',
//       {},
//       simpleMediaCallback,
//       'after'
//     );

//     function simpleMediaCallback() {
//       require(['plugins/gsap/gsap', 'plugins/gsap/ScrollTrigger'], (gsapModule, ScrollTriggerModule) => {


//         window.WDNPluginsExecuting--;
//         if (define.origAmd === undefined && window.WDNPluginsExecuting === 0) {
//           define.origAmd = define.amd;
//           delete define.amd;
//         }
//       });
//     }

//   }, false);
