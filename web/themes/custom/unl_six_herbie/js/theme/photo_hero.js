// Code block is commented until framework 6 update (removal of require.js)

// window.addEventListener('inlineJSReady', function () {
//   if (define.amd === undefined) {
//     define.amd = define.origAmd;
//     delete define.origAmd;
//   }
//   window.WDNPluginsExecuting++;

//   WDN.initializePlugin(
//     'scroll-animations',
//     {},
//     photoHeroCallback,
//     'after'
//   );

//   function photoHeroCallback() {
//     require(['plugins/gsap/gsap', 'plugins/gsap/ScrollTrigger'], (gsapModule, ScrollTriggerModule) => {
//       let animations = [];
//       let motionQuery = matchMedia('(prefers-reduced-motion)');
//       const gsap = gsapModule.gsap;

//       const handleReduceMotion = () => {
//         if (motionQuery.matches) {
//           animations.forEach((singleAnimation) => {
//             singleAnimation.progress(1).pause();
//           });
//         } else {
//           animations.forEach((singleAnimation) => {
//             singleAnimation.play();
//           });
//         }
//       };

//       motionQuery.addEventListener('change', handleReduceMotion);

//       animations.push(
//         gsap.to('.unlcms-slow-zoom-in', {
//           scale: 1.13,
//           duration: 30,
//           paused: true
//         })
//       );

//       handleReduceMotion();

//       window.WDNPluginsExecuting--;
//       if (define.origAmd === undefined && window.WDNPluginsExecuting === 0) {
//         define.origAmd = define.amd;
//         delete define.amd;
//       }
//     });
//   }

// }, false);
