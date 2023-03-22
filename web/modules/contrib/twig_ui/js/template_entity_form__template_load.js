/**
 * @file
 * Provides JS for the Twig UI TwigTemplateForm form.
 */

(function (Drupal, $, once, drupalSettings) {
  Drupal.behaviors.twigUiTemplateForm = {
    attach: function (context, settings) {
      $(document).ready(function () {
        // React to changes in the 'Theme' field.
        $(once('themeChange', "select[name=theme]")).change(function (e) {
          var theme = this.value;

          $.getJSON(drupalSettings.path.baseUrl + `ajax/twig-ui/template-list-load/${theme}`, function (data) {
            var resultsSelect = $("select[name='template']");

            // Replace select options.
            resultsSelect.empty();
            resultsSelect.append(`<option value='_none'>- Select -</option>`);
            $.each(data, function (index, value) {
              resultsSelect.append(`<option value='${value}'>${value}</option>`);
            });
          });
        });

        // React to changes in the 'Template' field.
        $(once('templateChange', "select[name=template]")).change(function (e) {
          var theme = $("select[name=theme]").val();
          var template = this.value;

          $.getJSON(drupalSettings.path.baseUrl + `ajax/twig-ui/template-load/${theme}/${template}`, function (data) {
            var templateCodeElement = $('.template-code pre');

            // Replace sample code.
            templateCodeElement.html(data.escaped_code);

            // Update file path in markup.
            if (template != '_none') {
              $('.file-path span.value').html(data.file_path);
            }
            else {
              $('.file-path span.value').html('');
            }
          });
        });

        // Insert template code into 'Template code' field.
        $(once('templateInsert', "input[name=template-insert]")).click(function (e) {
          e.preventDefault();

          var theme = $("select[name=theme]").val();
          var template = $("select[name=template]").val();

          if (theme == '_none' || template == '_none') {
            alert('Please select a theme and a template.');
            return;
          }

          $.getJSON(drupalSettings.path.baseUrl + `ajax/twig-ui/template-load/${theme}/${template}`, function (data) {

            // If CodeMirror is enabled.
            if ($('.CodeMirror').length > 0) {
              $('.CodeMirror')[0].CodeMirror.getDoc().setValue(data.raw_code);
            }
            // If CodeMirror isn't enabled.
            else {
              $('textarea[name=template_code]').val(data.raw_code);
            }

            // Scroll to the the Template Code field.
            var scroll = $(".field-template-code").offset().top;
            // Add offset for Toolbar.
            var toolbarHeight = $("#toolbar-bar").height();
            if ('undefined' !== typeof toolbarHeight) {
              scroll = scroll - toolbarHeight;
            }
            // Add offset for Toolbar tray.
            var trayHeight = $(".toolbar-tray.is-active").height();
            if ('undefined' !== typeof trayHeight) {
              scroll = scroll - trayHeight;
            }
            $(document).scrollTop(scroll);
          });
        });
      });
    }
  };
})(Drupal, jQuery, once, drupalSettings);
