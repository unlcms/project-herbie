<?php

namespace Drupal\layout_builder_styles\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\layout_builder_styles\LayoutBuilderStyleGroupInterface;

/**
 * Defines the LayoutBuilderGroup config entity.
 *
 * @ConfigEntityType(
 *   id = "layout_builder_style_group",
 *   label = @Translation("Layout builder style group"),
 *   label_collection = @Translation("Layout builder style groups"),
 *   label_plural = @Translation("Layout builder style groups"),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "list_builder" = "Drupal\layout_builder_styles\LayoutBuilderStyleGroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\layout_builder_styles\Form\LayoutBuilderStyleGroupForm",
 *       "edit" = "Drupal\layout_builder_styles\Form\LayoutBuilderStyleGroupForm",
 *       "delete" = "Drupal\layout_builder_styles\Form\LayoutBuilderStyleGroupDeleteForm"
 *     }
 *   },
 *   config_prefix = "group",
 *   admin_permission = "manage layout builder style groups",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "multiselect",
 *     "form_type",
 *     "required",
 *     "weight",
 *     "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/layout_builder_style/group/{layout_builder_style_group}/edit",
 *     "delete-form" = "/admin/config/content/layout_builder_style/group/{layout_builder_style_group}/delete",
 *     "collection" = "/admin/config/content/layout_builder_style/group",
 *     "add-form" = "/admin/config/content/layout_builder_style/group/add"
 *   }
 * )
 */
class LayoutBuilderStyleGroup extends ConfigEntityBase implements LayoutBuilderStyleGroupInterface {

  /**
   * The Layout Builder Style Group ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Layout Builder Style Group label.
   *
   * @var string
   */
  protected $label;

  /**
   * A string indicating the multiple selection setting.
   *
   * @var string
   */
  protected $multiselect;

  /**
   * A string indicating the selection form element setting.
   *
   * @var string
   */
  protected $form_type;

  /**
   * A boolean indicating whether the selection is required.
   *
   * @var bool
   */
  protected $required;

  /**
   * Order of style on the config page & in Layout Builder add/update forms.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getMultiselect() {
    return $this->multiselect;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormType() {
    return $this->form_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequired() {
    return $this->required;
  }

}
