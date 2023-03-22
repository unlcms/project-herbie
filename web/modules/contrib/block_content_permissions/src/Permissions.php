<?php

namespace Drupal\block_content_permissions;

use Drupal\block_content\BlockContentTypeInterface;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides dynamic permissions for the block content permissions module.
 */
class Permissions {

  use StringTranslationTrait;

  /**
   * Gets permissions.
   *
   * @return array
   *   Array of permissions.
   */
  public function get() {
    $permissions = [];

    // Generate permissions for all block content types.
    foreach (BlockContentType::loadMultiple() as $type) {
      $permissions += $this->buildPermissions($type);
    }

    return $permissions;
  }

  /**
   * Returns a list of block content permissions for a given type.
   *
   * @param \Drupal\block_content\BlockContentTypeInterface $type
   *   The block content type.
   *
   * @return array
   *   Array of permissions.
   */
  protected function buildPermissions(BlockContentTypeInterface $type) {
    $type_id = $type->id();
    $type_name = ['%type_name' => $type->label()];

    return [
      "create $type_id block content" => [
        'title' => $this->t('%type_name: Create new block content', $type_name),
      ],
      "delete any $type_id block content" => [
        'title' => $this->t('%type_name: Delete any block content', $type_name),
      ],
      "update any $type_id block content" => [
        'title' => $this->t('%type_name: Edit any block content', $type_name),
      ],
    ];
  }

}
