<?php

namespace Drupal\Tests\menu_admin_per_menu\Traits;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * Provides methods to create menu_content_links from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait MenuLinkContentTrait {

  use RandomGeneratorTrait;

  /**
   * Creates a menu link content based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'menu_name' => 'foo'.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The created menu link.
   */
  protected function createMenuContentLink(array $values = []): MenuLinkContentInterface {
    $menu_link = MenuLinkContent::create($values + [
      'title' => $this->randomMachineName(),
      'menu_name' => 'main',
      'link' => ['uri' => 'route:<front>'],
      'provider' => 'menu_link_content',
    ]);
    $menu_link->save();
    return $menu_link;
  }

}
