<?php

/**
 * @file
 * Post-update functions for Layout Builder Styles module.
 */

use Drupal\layout_builder_styles\LayoutBuilderStyleInterface;
use Drupal\layout_builder_styles\Entity\LayoutBuilderStyleGroup;

/**
 * Add newly-available layout restriction value to existing style entities.
 */
function layout_builder_styles_post_update_update_add_layout_restrictions() {
  $styles = \Drupal::entityTypeManager()
    ->getStorage('layout_builder_style')
    ->loadByProperties();
  /** @var \Drupal\layout_builder_styles\Entity\LayoutBuilderStyle $style */
  foreach ($styles as $style) {
    // Re-save existing styles with empty layout restrictions.
    if ($style->getType() === LayoutBuilderStyleInterface::TYPE_SECTION) {
      $style->set('layout_restrictions', []);
      $style->save();
    }
  }
}

/**
 * Add new 'administer layout builder styles' perm to roles.
 */
function layout_builder_styles_post_update_add_new_perms() {
  // Grant our new permissions to any role with the
  // 'administer site configuration' permission, which is what was
  // previously used to control access to this module.
  $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
  foreach ($roles as $role) {
    /** @var \Drupal\user\RoleInterface $role */
    if ($role->hasPermission('administer site configuration')) {
      $role->grantPermission('manage layout builder styles');
      $role->grantPermission('administer layout builder styles configuration');
      $role->save();
    }
  }
}

/**
 * Add defaults for config if not already set.
 */
function layout_builder_styles_post_update_fix_missing_config() {
  $config = \Drupal::configFactory()->getEditable('layout_builder_styles.settings');
  $update = FALSE;
  if (!$config->get('multiselect')) {
    $config->set('multiselect', 'single');
    $update = TRUE;
  }
  if (!$config->get('form_type')) {
    $config->set('form_type', 'checkboxes');
    $update = TRUE;
  }
  if ($update) {
    $config->save();
  }
}

/**
 * Add "default" Layout Builder Style Group to pre-existing styles.
 */
function layout_builder_styles_post_update_add_group() {
  // If groups are empty, create the default group.
  if (empty(LayoutBuilderStyleGroup::loadMultiple())) {
    $group = LayoutBuilderStyleGroup::create([
      'id' => 'default',
      'label' => 'Style',
      'weight' => 100,
      'required' => FALSE,
    ]);

    // We can carry over some settings from our old config.
    $legacy_config = \Drupal::configFactory()->getEditable('layout_builder_styles.settings');
    if ($legacy_config) {
      $group->set('multiselect', $legacy_config->get('multiselect') ?? 'single');
      $group->set('form_type', $legacy_config->get('form_type') ?? 'checkboxes');
    }
    else {
      // Unlikely scenario but covering bases if settings DNE.
      $group->set('multiselect', 'single');
      $group->set('form_type', 'checkboxes');
    }
    $group->save();

    // Don't need old config anymore.
    $legacy_config->delete();
    \Drupal::logger('layout_builder_styles')->info('Legacy settings removed.');

    // Set this new 'default' group to any existing styles, since all styles
    // must have a group associated now.
    $styles = \Drupal::entityTypeManager()
      ->getStorage('layout_builder_style')
      ->loadByProperties();
    /** @var \Drupal\layout_builder_styles\Entity\LayoutBuilderStyle $style */
    foreach ($styles as $style) {
      if (empty($style->getGroup())) {
        $style->set('group', $group->id());
        $style->save();
      }
    }
  }
}
