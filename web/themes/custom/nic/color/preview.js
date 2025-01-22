
(function ($) {
    Drupal.color = {
        logoChanged: false,
        callback: function(context, settings, form, farb, height, width) {
            // Change the logo to be the real one.
            if (!this.logoChanged) {
                $('#preview #preview-logo img').attr('src', Drupal.settings.color.logo);
                this.logoChanged = true;
            }
            // Remove the logo if the setting is toggled off.
            if (Drupal.settings.color.logo == null) {
                $('div').remove('#preview-logo');
            }

            // Solid background.
            $('#preview', form).css('backgroundColor', $('#palette input[name="palette[bg]"]', form).val());

            // Header links.
            $('#preview-main-menu-links a', form).css('color', $('#palette input[name="palette[headerlink]"]', form).val());

            // Text preview.
            $('#preview #preview-content', form).css('color', $('#palette input[name="palette[text]"]', form).val());
            $('#preview #preview-content a', form).css('color', $('#palette input[name="palette[link]"]', form).val());

            // Footer wrapper background.
            $('#preview #preview-footer-wrapper', form).css('background-color', $('#palette input[name="palette[footer]"]', form).val());

            // Footer text.
            $('#preview #preview-footer-wrapper .content', form).css('color', $('#palette input[name="palette[footertext]"]', form).val());

            // CSS3 Gradients: header.
            var gradient_start = $('#palette input[name="palette[top]"]', form).val();
            var gradient_end = $('#palette input[name="palette[bottom]"]', form).val();

            $('#preview #preview-header', form).attr('style', "background-color: " + gradient_start + "; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(" + gradient_start + "), to(" + gradient_end + ")); background-image: -moz-linear-gradient(-90deg, " + gradient_start + ", " + gradient_end + ");");

            // CSS3 Gradients: stripe.
            var gradient_start = $('#palette input[name="palette[stripestart]"]', form).val();
            var gradient_end = $('#palette input[name="palette[stripeend]"]', form).val();

            $('#preview #preview-header-stripe', form).attr('style', "background: transparent linear-gradient(to right, " + gradient_start + ", " + gradient_end + ") 0 0 repeat-x");
        }
    };
})(jQuery);
