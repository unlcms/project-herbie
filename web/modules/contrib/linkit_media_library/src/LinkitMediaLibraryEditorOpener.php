<?php

namespace Drupal\linkit_media_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\media_library\MediaLibraryOpenerInterface;
use Drupal\media_library\MediaLibraryState;

/**
 * The media library opener for text editors.
 *
 * @see \Drupal\media_library\Plugin\CKEditorPlugin\LinkitMediaLibrary
 */
class LinkitMediaLibraryEditorOpener implements MediaLibraryOpenerInterface {

  /**
   * The text format entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $filterStorage;

  /**
   * The media storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * The MediaLibraryEditorOpener constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->filterStorage = $entity_type_manager->getStorage('filter_format');
    $this->mediaStorage = $entity_type_manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    $filter_format_id = $state->getOpenerParameters()['filter_format_id'];

    /** @var \Drupal\filter\FilterFormatInterface $filter_format */
    $filter_format = $this->filterStorage->load($filter_format_id);
    if (empty($filter_format)) {
      return AccessResult::forbidden()
        ->addCacheTags(['filter_format_list'])
        ->setReason("The text format '$filter_format_id' could not be loaded.");
    }

    /** @var Drupal\filter\FilterPluginCollection $filters */
    $filters = $filter_format->filters();
    return $filter_format->access('use', $account, TRUE)
      ->andIf(AccessResult::allowedIf($filters->has('linkit') && $filters->get('linkit')->status === TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids) {
    /** @var \Drupal\media\MediaInterface $selected_media */
    $selected_media = $this->mediaStorage->load(reset($selected_ids));
    $response = new AjaxResponse();
    $values = [
      'attributes' => [
        'data-entity-bundle' => $selected_media->bundle(),
        'data-entity-type' => 'media',
        'data-entity-substitution' => 'media',
        'data-entity-uuid' => $selected_media->uuid(),
        'href' => '/media/' . $selected_media->id(),
        'target' => '_blank',
      ],
    ];
    $response->addCommand(new EditorDialogSave($values));
    return $response;
  }

}
