/**
 * @file
 * CodeMirror toolbar.
 */

(function ($, Drupal, baseUrl, cookies) {

  'use strict';

  /**
   * Creates a toolbar.
   *
   * @param {object} editor
   *   The editor instance.
   * @param {object} options
   *   The editor options.
   */
  Drupal.codeMirrorToolbar = function (editor, options) {
    editor.$toolbar = $('<div class="cme-toolbar"/>')
      .prependTo($(editor.getWrapperElement()));
    createButtons(editor, options);
    createModeSelect(editor, options);
  };

  /**
   * Creates editor buttons.
   */
  function createButtons(editor, options) {

    $('<div class="cme-buttons"/>')
      .prependTo(editor.$toolbar)
      .load(options.buttonsBaseUrl);

    var buttonTranslations = {
      'bold': Drupal.t('Bold'),
      'italic': Drupal.t('Italic'),
      'underline': Drupal.t('Underline'),
      'strike-through': Drupal.t('Strike through'),
      'list-numbered': Drupal.t('Numbered list'),
      'list-bullet': Drupal.t('Bullet list'),
      'link': Drupal.t('Link'),
      'horizontal-rule': Drupal.t('Horizontal rule'),
      'undo': Drupal.t('Undo'),
      'redo': Drupal.t('Redo'),
      'clear-formatting': Drupal.t('Clear formatting'),
      'enlarge': Drupal.t('Enlarge'),
      'shrink': Drupal.t('Shrink')
    };

    options.buttons.forEach(function (button) {
      var markup = [
        '<button data-cme-button="' + button + '" class="cme-button">',
        '<svg focusable="false" aria-hidden="true"><use xlink:href="#icon-' + button + '"></use></svg>',
        '<span class="visually-hidden">' + buttonTranslations[button] + '</span>',
        '</button>'
      ];
      $(markup.join('')).appendTo(editor.$toolbar);
    });
    editor.$toolbar.find('[data-cme-button="shrink"]').hide();

    function setFullScreen(state) {
      editor.setOption('fullScreen', state);
      editor.$toolbar.find('button[data-cme-button="enlarge"]').toggle(!state);
      editor.$toolbar.find('button[data-cme-button="shrink"]').toggle(state);
    }

    var extraKeys = {
      F11: function (editor) {
        setFullScreen(!editor.getOption('fullScreen'));
      },
      Esc: function () {
        setFullScreen(false);
      }
    };
    editor.setOption('extraKeys', extraKeys);

    var doc = editor.getDoc();

    function createHtmlList(type) {
      var list = '<' + type + '>\n';
      doc.getSelection().split('\n').forEach(function (value) {
        list += '  <li>' + value + '</li>\n';
      });
      list += '</' + type + '>';
      return list;
    }

    function replaceSelection(replacement, block) {
      var cursorTo = doc.getCursor('to');
      if (block && cursorTo.line === doc.lastLine()) {
        replacement += "\n";
      }
      doc.replaceSelection(replacement, doc.getCursor());
      var endsWithNewLine = replacement.charAt(replacement.length - 1) === "\n";
      var cursorAfter = doc.getCursor();
      var ch = endsWithNewLine ? 0 : cursorAfter.ch + replacement.length;
      var newLines = replacement.split("\n").length - 1;
      doc.setCursor({line: cursorAfter.line + newLines, ch: ch});
    }

    function buttonClickHandler(event) {
      var button = $(event.target).closest('[data-cme-button]').data('cme-button');
      switch (button) {

        case 'bold':
          replaceSelection('<strong>' + doc.getSelection() + '</strong>');
          break;

        case 'italic':
          replaceSelection('<em>' + doc.getSelection() + '</em>');
          break;

        case 'underline':
          replaceSelection('<u>' + doc.getSelection() + '</u>');
          break;

        case 'strike-through':
          replaceSelection('<s>' + doc.getSelection() + '</s>');
          break;

        case 'list-numbered':
          replaceSelection(createHtmlList('ol'), true);
          break;

        case 'list-bullet':
          replaceSelection(createHtmlList('ul'), true);
          break;

        case 'link':
          replaceSelection('<a href="">' + doc.getSelection() + '</a>');
          break;

        case 'horizontal-rule':
          var cursorFrom = doc.getCursor('from');
          var cursorTo = doc.getCursor('to');
          var line = doc.getLine(cursorFrom.line);

          var replacement = '<hr/>';
          if (cursorFrom.ch > 0) {
            replacement = "\n" + '<hr/>';
          }
          if (cursorTo.ch < line.length) {
            replacement += "\n";
          }
          replaceSelection(replacement, true);
          break;

        case 'undo':
          doc.undo();
          break;

        case 'redo':
          doc.redo();
          break;

        case 'clear-formatting':
          doc.replaceSelection($('<div>' + doc.getSelection() + '</div>').text(), doc.getCursor());
          break;

        case 'enlarge':
          setFullScreen(true);
          break;

        case 'shrink':
          setFullScreen(false);
          break;
      }
      editor.focus();
      return false;
    }
    editor.$toolbar.click(buttonClickHandler);
  }

  /**
   * Creates a select list of available modes.
   */
  function createModeSelect(editor, options) {
    if (!$.isEmptyObject(options.modeSelect)) {
      var selectOptions = '';
      for (var key in options.modeSelect) {
        if (options.modeSelect.hasOwnProperty(key)) {
          selectOptions += '<option value="' + key + '">' + options.modeSelect[key] + '</option>';
        }
      }
      $('<select class="cme-mode"/>')
        .append(selectOptions)
        .val(options.mode)
        .change(function () {
          var value = $(this).val();
          editor.setOption('mode', value);
          // Save the value to cookie.
          var modesEncoded = cookies.get('codeMirrorModes');
          var modes = modesEncoded ? JSON.parse(modesEncoded) : {};
          modes[editor.getTextArea().getAttribute('data-drupal-selector')] = value;
          cookies.set('codeMirrorModes', JSON.stringify(modes), { path: baseUrl });
        })
        .appendTo(editor.$toolbar);
    }
  }

}(jQuery, Drupal, drupalSettings.path.baseUrl, window.Cookies));
