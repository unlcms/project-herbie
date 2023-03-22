<?php

namespace Drupal\layout_builder_styles\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\layout_builder_styles\LayoutBuilderStyleInterface;

/**
 * Defines the LayoutBuilderStyle config entity.
 *
 * @ConfigEntityType(
 *   id = "layout_builder_style",
 *   label = @Translation("Layout builder style"),
 *   label_collection = @Translation("Layout builder styles"),
 *   label_plural = @Translation("Layout builder styles"),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "list_builder" = "Drupal\layout_builder_styles\LayoutBuilderStyleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\layout_builder_styles\Form\LayoutBuilderStyleForm",
 *       "edit" = "Drupal\layout_builder_styles\Form\LayoutBuilderStyleForm",
 *       "delete" = "Drupal\layout_builder_styles\Form\LayoutBuilderStyleDeleteForm"
 *     }
 *   },
 *   config_prefix = "style",
 *   admin_permission = "manage layout builder styles",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "classes" = "classes",
 *     "type" = "type",
 *     "group" = "group",
 *     "weight" = "weight",
 *     "block_restrictions" = "block_restrictions",
 *     "layout_restrictions" = "layout_restrictions",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/layout_builder_style/{layout_builder_style}/edit",
 *     "delete-form" = "/admin/config/content/layout_builder_style/{layout_builder_style}/delete",
 *     "collection" = "/admin/config/content/layout_builder_style",
 *     "add-form" = "/admin/config/content/layout_builder_style/add"
 *   }
 * )
 */
class LayoutBuilderStyle extends ConfigEntityBase implements LayoutBuilderStyleInterface {

  /**
   * The Layout Builder Style ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Layout Builder Style label.
   *
   * @var string
   */
  protected $label;

  /**
   * A string containing the classes, one per line.
   *
   * @var string
   */
  protected $classes;

  /**
   * A string indicating if this style applies to sections or components.
   *
   * @var string
   */
  protected $type;

  /**
   * A string indicating the group of this style (eg, Spacing, Color).
   *
   * @var string
   */
  protected $group;

  /**
   * Order of style on the config page & in Layout Builder add/update forms.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * A list of blocks to limit this style to.
   *
   * @var array
   */
  protected $block_restrictions = [];

  /**
   * A list of layouts to limit this style to.
   *
   * @var array
   */
  protected $layout_restrictions = [];

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return $this->classes;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return empty($this->group) ? '' : $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockRestrictions() {
    return $this->block_restrictions ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutRestrictions() {
    return $this->layout_restrictions ?? [];
  }

}
