/**
 * @file
 * CodeMirror editor behaviors.
 */

(function ($, Drupal, debounce, defaultOptions, cookies) {

  'use strict';

  var editors = {};
  Drupal.editors.codemirror_editor = {
    attach: function attach(element, format) {
      editors[element.id] = init(element, format.editorSettings);
    },
    detach: function (element, format, trigger) {
      if (trigger !== 'serialize') {
        editors[element.id].toTextArea(element);
      }
    },
    onChange: function (element, callback) {
      editors[element.id].on('change', debounce(callback, 500));
    }
  };

  var warn = true;
  Drupal.behaviors.codeMirrorEditor = {

    attach: function () {
      var $textAreas = $(once('codemirror-editor', 'textarea[data-codemirror]'));

      // Only check library when at least once CodeMirror textarea presented on
      // the page.
      if ($textAreas.length && typeof CodeMirror === 'undefined' && warn) {
        console.warn(Drupal.t('CodeMirror library is not loaded!'));
        warn = false;
        return;
      }      
      $.each($textAreas, function (key, textArea) {
        init(textArea);
      });
    },

    detach: function () {
      // CodeMirror tracks form submissions to update textareas but this does
      // not work ajax requests. So we save data manually.
      var $editors = $(once('codemirror-editor', '.CodeMirror'))
      $.each($editors, function (key, editor) {
        editor.CodeMirror.save();
      });
    }

  };

  /**
   * Initializes CodeMirror editor for a given textarea.
   */
  function init(textArea, options) {

    var $textArea = $(textArea);
    options = options || $textArea.data('codemirror');
    options = jQuery.extend({}, defaultOptions, options);

    // Remove "required" attribute because the textarea is not focusable.
    $textArea.removeAttr('required');

    // Create HTML/Twig overlay mode.
    CodeMirror.defineMode('html_twig', function (config, parserConfig) {
      return CodeMirror.overlayMode(
        CodeMirror.getMode(config, parserConfig.backdrop || 'text/html'),
        CodeMirror.getMode(config, 'twig')
      );
    });

    // Load language mode from cookie if possible.
    var modesEncoded = cookies.get('codeMirrorModes');
    var modes = modesEncoded ? JSON.parse(modesEncoded) : {};
    options.mode = modes[$textArea.data('drupal-selector')] || options.mode;

    // Duplicate line command.
    CodeMirror.keyMap.pcDefault['Ctrl-D'] = function (cm) {
      var currentCursor = cm.doc.getCursor();
      var lineContent = cm.doc.getLine(currentCursor.line);
      CodeMirror.commands.goLineEnd(cm);
      CodeMirror.commands.newlineAndIndent(cm);
      cm.doc.replaceSelection(lineContent.trim());
      cm.doc.setCursor(currentCursor.line + 1, currentCursor.ch);
    };

    // Comment line command.
    CodeMirror.keyMap.pcDefault['Ctrl-/'] = function (cm) {
      cm.toggleComment();
    };

    var editor = CodeMirror.fromTextArea(textArea, {
      // The theme cannot be changed per textarea because this would require
      // loading CSS files for all available themes.
      theme: options.theme,
      lineWrapping: options.lineWrapping,
      lineNumbers: options.lineNumbers,
      mode: options.mode,
      readOnly: options.readOnly,
      foldGutter: options.foldGutter,
      autoCloseTags: options.autoCloseTags,
      styleActiveLine: options.styleActiveLine,
      // The plugin tracks mouseup and keyup events. So no need to poll the
      // editor every 250ms (default delay value).
      autoRefresh: {delay: 3000}
    });

    var $wrapper = $(editor.getWrapperElement());

    // Set helper class to hide cursor when the text area is read only.
    // See https://github.com/codemirror/CodeMirror/issues/1099.
    if (options.readOnly) {
      $wrapper.addClass('cme-readonly');
    }

    // Bubble error class.
    if ($textArea.hasClass('error')) {
      $wrapper.addClass('cme-error');
    }

    if (options.foldGutter) {
      editor.setOption('gutters', ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']);
    }

    editor.setSize(options.width, options.height);
    editor.getScrollerElement().style.minHeight = $textArea.height() + 'px';

    if (options.toolbar) {
      Drupal.codeMirrorToolbar(editor, options);
    }

    return editor;
  }

}(jQuery, Drupal, Drupal.debounce, drupalSettings.codeMirrorEditor, window.Cookies));
