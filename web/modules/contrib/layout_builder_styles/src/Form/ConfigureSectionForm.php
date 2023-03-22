<?php

namespace Drupal\layout_builder_styles\Form;

use Drupal\layout_builder\Form\ConfigureSectionForm as OriginalConfigureSectionForm;

/**
 * Class ConfigureSectionForm.
 *
 * Extend the original form to expose the layout object.
 * See https://www.drupal.org/i/3044117
 */
class ConfigureSectionForm extends OriginalConfigureSectionForm {

  /**
   * Get the layout plugin being modified.
   *
   * @return \Drupal\Core\Layout\LayoutInterface|\Drupal\Core\Plugin\PluginFormInterface
   *   The layout plugin object.
   */
  public function getLayout() {
    return $this->layout;
  }

}
