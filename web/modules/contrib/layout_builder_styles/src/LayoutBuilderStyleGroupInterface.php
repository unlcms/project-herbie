<?php

namespace Drupal\layout_builder_styles;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Layout Builder style group entities.
 */
interface LayoutBuilderStyleGroupInterface extends ConfigEntityInterface {

  const TYPE_CHECKBOXES = 'checkboxes';
  const TYPE_MULTIPLE_SELECT = 'multiple-select';

  const TYPE_SINGLE = 'single';
  const TYPE_MULTIPLE = 'multiple';

  /**
   * Returns the group of style (eg, margin, padding, color_scheme).
   *
   * @return string
   *   Either "checkboxes" or "multiple-select".
   */
  public function getFormType();

  /**
   * Returns list of block plugin IDs to restrict this style to.
   *
   * @return string
   *   Either "single" or "multiple"
   */
  public function getMultiselect();

  /**
   * Returns whether the group requires input.
   *
   * @return bool
   *   Either TRUE or FALSE
   */
  public function getRequired();

}
