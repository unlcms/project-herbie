import { Plugin } from 'ckeditor5/src/core';
import { View } from 'ckeditor5/src/ui';

class Infotext extends Plugin {

  init() {
    const editor = this.editor;
    const linkUI = editor.plugins.get('LinkUI');

    // Save the original _createFormView method
    const originalCreateFormView = linkUI._createFormView.bind(linkUI);

    // Override it to inject our help text
    linkUI._createFormView = () => {
      const formView = originalCreateFormView();

      const helpTextView = new View();

      helpTextView.setTemplate({
        tag: 'div',
        attributes: {
          class: ['ck', 'ck-help-text'],
          style: 'margin: 9px 0 0 0; background-color: #f8f8f8; padding: 3px; font-size: 0.8em; color: #444;'
        },
        children: [
          {
            tag: 'span',
            children: [
              { text: 'Files can be uploaded to the ' },
              {
                tag: 'a',
                attributes: {
                  href: '/media/add',
                  target: '_blank',
                  style: 'color: #0066cc; text-decoration: underline; cursor: pointer;'
                },
                children: [
                  { text: 'Media Library' }
                ]
              },
              { text: '.'},
              {
                tag: 'br'
              },
              { text: 'Once uploaded they will be searchable here.' }
            ]
          }
        ]
      });

      // Insert help text after the URL input (which is the first child)
      formView.children.add(helpTextView);

      return formView;
    };
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'Infotext';
  }
}

export default {
  Infotext,
};
