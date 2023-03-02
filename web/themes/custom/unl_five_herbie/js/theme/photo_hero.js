window.addEventListener('inlineJSReady', function() {

  WDN.initializePlugin('scroll-animations');

  require(['plugins/gsap/gsap', 'plugins/gsap/ScrollTrigger'], (gsapModule, ScrollTriggerModule) => {
      let animations = [];
      let motionQuery = matchMedia('(prefers-reduced-motion)');
      const gsap = gsapModule.gsap;

      const handleReduceMotion = () => {
          if (motionQuery.matches) {
              animations.forEach((singleAnimation) => {
                  singleAnimation.progress(1).pause();
              });
          } else {
              animations.forEach((singleAnimation) => {
                  singleAnimation.play();
              });
          }
      };

      motionQuery.addEventListener('change', handleReduceMotion);

      animations.push(
        gsap.to('.unlcms-slow-zoom-in', {
          scale: 1.13,
          duration: 30,
          paused: true
        })
      );

      handleReduceMotion();
  })

});
