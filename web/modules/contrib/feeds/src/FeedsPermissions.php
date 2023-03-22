<?php

namespace Drupal\feeds;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\feeds\Entity\FeedType;

/**
 * Defines a class containing permission callbacks.
 */
class FeedsPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of content permissions.
   *
   * @return array
   *   Permissions for operations on feeds.
   */
  public function contentPermissions() {
    return [
      'access feed overview' => [
        'title' => $this->t('Access the Feed overview page'),
        'description' => $this->t('Get an overview of <a href=":url">all feeds</a>.', [':url' => Url::fromRoute('feeds.admin')]),
      ],
    ];
  }

  /**
   * Returns an array of feeds type permissions.
   *
   * @return array
   *   Permissions for operations on feed types.
   */
  public function feedTypePermissions() {
    $perms = [];
    // Generate feeds permissions for all feeds types.
    foreach (FeedType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of feeds permissions for a given type.
   *
   * @param \Drupal\feeds\Entity\FeedType $feed_type
   *   The feed type object.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(FeedType $feed_type) {
    $args = ['%name' => $feed_type->label()];
    $id = $feed_type->id();

    return [
      "view $id feeds" => [
        'title' => $this->t('%name: View feeds', $args),
      ],
      "view own $id feeds" => [
        'title' => $this->t('%name: View own feeds', $args),
      ],
      "create $id feeds" => [
        'title' => $this->t('%name: Create new feeds', $args),
      ],
      "update $id feeds" => [
        'title' => $this->t('%name: Update existing feeds', $args),
      ],
      "update own $id feeds" => [
        'title' => $this->t('%name: Update own existing feeds', $args),
      ],
      "delete $id feeds" => [
        'title' => $this->t('%name: Delete feeds', $args),
      ],
      "delete own $id feeds" => [
        'title' => $this->t('%name: Delete own feeds', $args),
      ],
      "import $id feeds" => [
        'title' => $this->t('%name: Import feeds', $args),
      ],
      "import own $id feeds" => [
        'title' => $this->t('%name: Import own feeds', $args),
      ],
      "schedule_import $id feeds" => [
        'title' => $this->t('%name: Import feeds in background', $args),
      ],
      "schedule_import own $id feeds" => [
        'title' => $this->t('%name: Import own feeds in background', $args),
      ],
      "clear $id feeds" => [
        'title' => $this->t('%name: Delete feed items', $args),
      ],
      "clear own $id feeds" => [
        'title' => $this->t('%name: Delete own feed items', $args),
      ],
      "unlock $id feeds" => [
        'title' => $this->t('%name: Unlock feeds', $args),
        'description' => $this->t('If a feed importation breaks for some reason, users with this permission can unlock it.'),
      ],
      "unlock own $id feeds" => [
        'title' => $this->t('%name: Unlock own feeds', $args),
        'description' => $this->t('If a feed importation breaks for some reason, users with this permission can unlock it.'),
      ],
    ];
  }

}
