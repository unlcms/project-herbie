import { Plugin } from 'ckeditor5/src/core';

class Infotext extends Plugin {
  init() {
    this._state = {};
    const editor = this.editor;

    editor.plugins.get( 'LinkUI' ).formView.urlInputView.infoText = 'Files can be uploaded at Content --> Media. Once uploaded they will be searchable here.';

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
