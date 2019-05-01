(function ($, CKEDITOR) {
  CKEDITOR.plugins.add( 'ckeditor_dcf_table',{
    init: function( editor ) {
      CKEDITOR.on('instanceReady', function( ev ) {
        // 'saveSnapshot' event fires on both element insert and style selection.
        editor.on("saveSnapshot", function (ev) {
          var doc = ev.editor.document.$;
          $(doc).find('table').each(function() {
            if (!$(this).hasClass('dcf-table')) {
              $(this).addClass('dcf-table');
            }
          });
        });
      });
    }
  });
})(jQuery, CKEDITOR);