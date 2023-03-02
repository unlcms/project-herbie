<?php

namespace Drupal\media_embed_view_mode_restrictions;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryEditorOpener;
use Drupal\media_library\MediaLibraryOpenerInterface;

/**
 * Decorates MediaLibraryEditorOpener class provided by Media Library module.
 */
final class MediaLibraryEditorOpenerDecorator implements MediaLibraryOpenerInterface {

  /**
   * The decorated media library opener for text editors.
   *
   * @var \Drupal\media_library\MediaLibraryEditorOpener
   */
  protected $decorated;

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
    $this->decorated = new MediaLibraryEditorOpener($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    return $this->decorated->checkAccess($state, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids) {
    $selected_media = $this->mediaStorage->load(reset($selected_ids));

    $response = new AjaxResponse();
    $values = [
      'attributes' => [
        'data-entity-type' => 'media',
        'data-entity-uuid' => $selected_media->uuid(),
        'data-align' => 'center',
      ],
    ];

    // Set 'data-view-mode' attribute if a default view mode is configured
    // for the filter format.
    $media_library_opener_parameters = $state->get('media_library_opener_parameters');
    $filter_format = $this->filterStorage->load($media_library_opener_parameters['filter_format_id']);
    if ($filter_format && $filter_format->filters('media_embed')) {
      $filter = $filter_format->filters('media_embed');
      $bundle = $selected_media->bundle();

      // Check if bundle-specific default view mode is configured.
      if (isset($filter->settings['bundle_view_modes'][$bundle])) {
        $default_view_mode = $filter->settings['bundle_view_modes'][$bundle]['default_view_mode'];
      }
      else {
        $default_view_mode = $filter->settings['default_view_mode'];
      }

      if ($default_view_mode) {
        $values['attributes']['data-view-mode'] = $default_view_mode;
      }
    }

    $response->addCommand(new EditorDialogSave($values));

    return $response;
  }

}
