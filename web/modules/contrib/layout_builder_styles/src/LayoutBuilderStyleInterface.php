<?php

namespace Drupal\layout_builder_styles;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Layout Builder style entities.
 */
interface LayoutBuilderStyleInterface extends ConfigEntityInterface {

  const TYPE_COMPONENT = 'component';
  const TYPE_SECTION = 'section';

  /**
   * Returns text from the classes field.
   *
   * @return string
   *   A string of classes.
   */
  public function getClasses();

  /**
   * Returns the type of LB item this applies to: "section" or "component".
   *
   * @return string
   *   Either "section" or "component".
   */
  public function getType();

  /**
   * Returns the grouping this style is placed in..
   *
   * @return string
   *   The group id this style applies to.
   */
  public function getGroup();

  /**
   * Returns list of block plugin IDs to restrict this style to.
   *
   * @return array
   *   The block plugin IDs to restrict this style to.
   */
  public function getBlockRestrictions();

}
