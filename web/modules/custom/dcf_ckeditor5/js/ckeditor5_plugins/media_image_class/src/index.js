import { Plugin } from 'ckeditor5/src/core';

import {
  createDropdown,
  addToolbarToDropdown,
} from 'ckeditor5/src/ui';

class MediaImageClass extends Plugin {

  init() {
    const editor = this.editor;
    const componentFactory = editor.ui.componentFactory;

    componentFactory.add('mediaImageClassDropdown', (locale) => {
      const dropdown = createDropdown(locale);

      dropdown.buttonView.set({
        label: 'Image styles',
        withText: true,
        tooltip: true,
      });

      const buttons = [
        componentFactory.create(
          'drupalElementStyle:imageClass:none'
        ),
        componentFactory.create(
          'drupalElementStyle:imageClass:frame_quad'
        ),
      ];

      buttons.forEach((button) => button.set({ withText: true }));

      addToolbarToDropdown(dropdown, buttons, { isVertical: true });

      return dropdown;
    });
  }

}

export default {
  MediaImageClass,
};
