Zenfonts([
        {family:"Gotham SSm A", style:"font-weight: 300"},
        {family:"Gotham SSm B", style:"font-weight: 300"},
        {family:"Verlag Cond A", style:"font-weight: 300"},
        {family:"Verlag Cond B", style:"font-weight: 300"}
        ],
        {fallbackClass: "fallback-gotham fallback-gotham fallback-verlag fallback-verlag", timeout: 1000});

(function ($) {

    $( document ).ready(function() {

        // Load Cloud.typography webfonts
        var css = $("<link>", {
            "rel" : "stylesheet",
            "type" : "text/css",
            "href" : "https://cloud.typography.com/7717652/736126/css/fonts.css"
        })[0];

        document
            .getElementsByTagName("head")[0]
            .appendChild(css);

        // Change focus to main-content after 'Skip Navigation'
        $( document ).ready(function() {
            // bind a click event to the 'Skip Navigation' link
            $(".skip").click(function(event){

                // strip the leading hash and declare
                // the content we're skipping to
                var skipTo="#"+this.href.split('#')[1];

                // Setting 'tabindex' to -1 takes an element out of normal
                // tab flow but allows it to be focused via javascript
                $(skipTo).attr('tabindex', -1).on('blur focusout', function () {

                    // when focus leaves this element,
                    // remove the tabindex attribute
                    $(this).removeAttr('tabindex');

                }).focus(); // focus on the content container
            });
        });

        // Open/close menu navigation
        $('#menu_toggle').click(function(e) {
            e.preventDefault();
            if ($('#nic_search').hasClass('state-visible-search')) {
                $('#nic_search').removeClass('state-visible-search');
            }
            $('#nic_menu').toggleClass('state-visible-menu');
            close_secondary_nav();
        });

        // Open/close search form
        $('#search_toggle').click(function(e) {
            e.preventDefault();
            if ($('#nic_menu').hasClass('state-visible-menu')) {
                $('#nic_menu').removeClass('state-visible-menu');
            }
            $('#nic_search').toggleClass('state-visible-search');
            close_secondary_nav();
            setTimeout(function() {
                $('#q').focus();
            },250);
        });

        // Close open secondary navigation when menu or search is toggled (closed)
        close_secondary_nav = function() {
            if (matchMedia('only all and (max-width: 751px)').matches) {

                if ($('.primary-menu li ul').hasClass('open')) {
                   setTimeout(function() {
                        $('.primary-menu li ul').slideUp('fast').removeClass('open');
                    },750);
                }
            }
        };

        // Remove mobile behavior if browser resized >= 752px
        var remove_mobile = function() {
            if (matchMedia('only all and (min-width: 752px)').matches) {

                $('.primary-menu li ul').removeAttr('style');
            }
        }
        $(function() {
            // Call on every window resize
            $(window).resize(remove_mobile);
            // Call once on initial load
            remove_mobile();
        });

        // Toggle accordion for menu on narrower browser widths
    	$('.primary-menu > li > a').click(function(e) {
    		if (matchMedia('only all and (max-width: 752px)').matches) {
    			var dropdown = $(this).next('ul');

                if (dropdown.length && !dropdown.hasClass('open')) {
                    e.preventDefault();
                    $('.primary-menu li ul').slideUp('fast').removeClass('open');
    				dropdown.slideDown('fast').addClass('open');
    			}
    		}
    	});

        // Open modal
        open_modal = function() {
            $('html,body').css('overflow','hidden'); // disable scroll
            $('.modal-content').attr('tabindex', '0'); // shift focus to modal
            $('.page').attr('aria-hidden', 'true'); // hide page for ARIA
        }

        // Close modal
        close_modal = function() {
            $('html,body').css('overflow','auto'); // enable scroll
            $('.modal-content').attr('tabindex', '-1'); // shift focus back to page
            $('.page').attr('aria-hidden', 'false'); // show page for ARIA
        }

        // Open home video
        $('#play_home_video').click(function(e) {
            e.preventDefault();
            open_modal();
            $('#intro_video').addClass('playing');
            $('.modal-overlay-home-video').attr('aria-hidden', 'false'); // show video modal for ARIA
            mejs.players[0].play();
        });

        // Close home video
        $('#close_home_video, .modal-overlay').click(function(e) {
            e.preventDefault();
            close_modal();
            $('#intro_video').removeClass('playing');
            $('.modal-overlay-home-video').attr('aria-hidden', 'true'); // hide video modal for ARIA
            mejs.players[0].pause();
        });
        $('#intro_video_player').click(function(e){
            e.stopPropagation();
        });

        // https://gist.github.com/nathansearles/271870d4100f0f045c5c
        // isAutoplaySupported(callback);
        // Test if HTML5 video autoplay is supported
        isAutoplaySupported = function(callback) {
            // Is the callback a function?
            if (typeof callback !== 'function') {
                console.log('isAutoplaySupported: Callback must be a function!');
                return false;
            }
            // Check if sessionStorage exist for autoplaySupported,
            // if so we don't need to check for support again
            if (!sessionStorage.autoplaySupported) {
                // Create video element to test autoplay
                var video = document.createElement('video');
                video.autoplay = true;
                video.src = 'data:video/mp4;base64,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';
                video.load();
                video.style.display = 'none';
                video.playing = false;
                video.play();
                // Check if video plays
                video.onplay = function() {
                    this.playing = true;
                };
                // Video has loaded, check autoplay support
                video.oncanplay = function() {
                    if (video.playing) {
                        sessionStorage.autoplaySupported = 'true';
                        callback(true);
                    } else {
                        sessionStorage.autoplaySupported = 'false';
                        callback(false);
                    }
                };
            } else {
                // We've already tested for support
                // use sessionStorage.autoplaySupported
                if (sessionStorage.autoplaySupported === 'true') {
                    callback(true);
                } else {
                    callback(false);
                }
            }
        }

        // Usage: isAutoplaySupported(callback);
        // Using a callback assures that support
        // has been properly checked
        isAutoplaySupported(function(supported) {
            if (supported) {
                // HTML5 Video Autoplay Supported!
                console.log('HTML5 Video Autoplay Supported!');
                $('html').addClass('videoautoplay');
            } else {
                // HTML5 Video Autoplay Not Supported :(
                console.log('HTML5 Video Autoplay Not Supported :(');
            }
        });

        // Disable pointer events (scrolling) on Google Maps until clicked
        $('.iframe-google-map').css('pointer-events', 'none');
        $('.map-canvas').on('click', function() {
        	$('.iframe-google-map').css('pointer-events', 'auto');
        });
        $( ".iframe-google-map" ).mouseleave(function() {
        	 $('.iframe-google-map').css('pointer-events', 'none');
        });

    	// Change home page hero position on scroll to avoid conflict with fixed position NIC Vision content further down the page
        var target = $('.front .not-hero').length ? $('.front .not-hero').offset().top : null,
    	    timeout = null;

        if (target) {
            $(window).scroll(function () {
                if (!timeout) {
                    timeout = setTimeout(function () {
                        console.log('scroll');
                        clearTimeout(timeout);
                        timeout = null;
                        if ($(window).scrollTop() >= target) {
                            $('.hero picture, .hero video').css('position', 'absolute');
                            $('.hero picture, .hero video').css({'visibility':'hidden', 'opacity':'0'});
                            $('.nic-vision-image').css({'visibility':'visible', 'opacity':'1'});
                        }
                        if ($(window).scrollTop() <= target) {
                            $('.hero picture, .hero video').css('position', 'fixed');
                            $('.hero picture, .hero video').css({'visibility':'visible', 'opacity':'1'});
                            $('.nic-vision-image').css({'visibility':'hidden', 'opacity':'0'});
                        }
                    }, 250);
                }
            });
        }

        // Validate email list signup form
        $('#form_email_list_signup').validate({
            rules: {
                email: {
                    required: true,
                    betterEmail: true
                }
            },
            /*errorPlacement: function(error, element) {
                error.appendTo('label[for='+$(element).attr('id')+']');
            },*/
            errorElement: 'em'
        });

        // Stricter email validation
        jQuery.validator.addMethod("betterEmail", function(value, element) {
            return this.optional( element ) || /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test( value );
        }, 'Please enter a valid email address.');

        //$('form').removeAttr('novalidate');

    });

})(jQuery);
