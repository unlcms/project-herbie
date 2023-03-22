<?php

namespace Drupal\menu_admin_per_menu\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific entity reference selection for the menu entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:menu",
 *   label = @Translation("Menu selection"),
 *   entity_types = {"menu"},
 *   group = "default",
 *   weight = 1
 * )
 */
class MenuSelection extends DefaultSelection {

  /**
   * The allowed menu service.
   *
   * @var \Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess
   */
  protected $allowedMenuService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setAllowedMenusService($container->get('menu_admin_per_menu.allowed_menus'));
    return $instance;
  }

  /**
   * Set the allowed menu service.
   *
   * @param \Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess $allowed_menu_service
   *   The allowed menu service.
   *
   * @return $this
   *   The current class.
   */
  public function setAllowedMenusService(MenuAdminPerMenuAccess $allowed_menu_service): self {
    $this->allowedMenuService = $allowed_menu_service;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable auto create.
    $form['auto_create']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    if (!$this->currentUser->hasPermission('administer menu')) {
      $menu_permissions = $this->allowedMenuService->getPerMenuPermissions($this->currentUser);
      $query->condition('id', $menu_permissions, 'IN');
    }

    return $query;
  }

}
