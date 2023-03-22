/**
 * @file
 * CodeMirror formatter behaviors.
 */

(function ($, Drupal, debounce, defaultOptions) {

  'use strict';

  Drupal.behaviors.codeMirrorEditor = {

    attach: function () {
      var $codeAreas = $(once('codemirror-editor', 'code[data-codemirror]'))
      $.each($codeAreas, function (key, codeArea) {
        init(codeArea);
      });
    }

  };

  /**
   * Initializes CodeMirror editor for a given code area.
   */
  function init(codeArea) {
    var $codeArea = $(codeArea);

    var options = $codeArea.data('codemirror');
    options = jQuery.extend({}, defaultOptions, options);

    if (options.foldGutter) {
      options.gutters = ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'];
    }

    // Remove line breaks from start and end of the code.
    options.value = $codeArea.text().replace(/^\n+|\n+$/g, '');

    // Replace code tag with a div and append CodeMirror to it.
    var $div = $('<div class="cme-wrapper"/>').insertAfter($codeArea);
    var editor = CodeMirror($div[0], options);
    $codeArea.remove();

    // Setting 'nocursor' CodeMirror option would make it impossible to copy
    // text from the code area. So we use CSS to hide cursor.
    $(editor.getWrapperElement()).addClass('cme-readonly');
    editor.setSize(options.width, options.height);

    return editor;
  }

}(jQuery, Drupal, Drupal.debounce, drupalSettings.codeMirrorFormatter));
