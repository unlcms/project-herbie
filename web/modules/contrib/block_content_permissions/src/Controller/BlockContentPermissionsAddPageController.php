<?php

namespace Drupal\block_content_permissions\Controller;

use Drupal\block_content\Controller\BlockContentController;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the block content add page.
 *
 * Extends normal controller to remove types based on create permission.
 */
class BlockContentPermissionsAddPageController extends BlockContentController {

  /**
   * The account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Override.
   *
   * Add current_user.
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('block_content'),
      $entity_manager->getStorage('block_content_type'),
      $container->get('theme_handler'),
      $container->get('current_user')
    );
  }

  /**
   * Override.
   *
   * Add AccountInterface.
   */
  public function __construct(EntityStorageInterface $block_content_storage, EntityStorageInterface $block_content_type_storage, ThemeHandlerInterface $theme_handler, AccountInterface $account) {
    $this->blockContentStorage = $block_content_storage;
    $this->blockContentTypeStorage = $block_content_type_storage;
    $this->themeHandler = $theme_handler;
    $this->account = $account;
  }

  /**
   * Override.
   *
   * Add create permission control over block content types.
   */
  public function add(Request $request) {
    $types = $this->blockContentTypeStorage->loadMultiple();

    // Remove block content types based on create permissions.
    $account = $this->account;
    foreach ($types as $bundle_type => $bundle_obj) {
      if (!$account->hasPermission("create $bundle_type block content")) {
        unset($types[$bundle_type]);
      }
    }

    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any block types yet. Go to the <a href=":url">block type creation page</a> to add a new block type.', [
          ':url' => Url::fromRoute('block_content.type_add')->toString(),
        ]),
      ];
    }

    return ['#theme' => 'block_content_add_list', '#content' => $types];
  }

}
