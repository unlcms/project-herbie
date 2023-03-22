<?php

namespace Drupal\Tests\features_permissions\Kernel;

use Drupal\Core\Form\FormState;

/**
 * Tests the Features CRUD event subscriber.
 *
 * @group features_permissions
 */
class FeaturesCrudTest extends FeaturesPermissionsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enableModules(['features', 'config_update']);
    $this->installEntitySchema('features_bundle');
    $this->installConfig(['features']);
  }

  /**
   * Tests features revert.
   */
  public function testFeatureRevert() {
    $auth_perms = \Drupal::service('entity_type.manager')
      ->getStorage('user_role')
      ->load('authenticated')
      ->getPermissions();

    // Verify authenticated roles has none of the permissions granted by the
    // user_test_feature feature.
    $this->assertFalse(in_array('cancel account', $auth_perms));
    $this->assertFalse(in_array('change own username', $auth_perms));
    $this->assertFalse(in_array('select account cancellation method', $auth_perms));

    // Enable the 'user_test_feature' feature.
    $this->enableModules(['user_test_feature']);

    // Features has an ::import() method in the FeaturesManager class; however,
    // it's not used anywhere. The ::submitForm() method on its
    // \Drupal\features_ui\Form\FeaturesDiffForm class is where the action
    // happens. Programmatically submit this form to test our event subscriber.
    $form_state = new FormState();
    $form = \Drupal::formBuilder()->buildForm(
      '\Drupal\features_ui\Form\FeaturesDiffForm',
      $form_state
    );
    $form_state->setValue('diff', [
      'features_permissions.permission.cancel_account' => 'features_permissions.permission.cancel_account',
      'features_permissions.permission.change_own_username' => 'features_permissions.permission.change_own_username',
      'features_permissions.permission.select_account_cancellation_method' => 'features_permissions.permission.select_account_cancellation_method',
    ]);
    \Drupal::formBuilder()->executeSubmitHandlers($form, $form_state);

    // Reload authenticated role.
    $auth_perms = \Drupal::service('entity_type.manager')
      ->getStorage('user_role')
      ->load('authenticated')
      ->getPermissions();

    // Verify authenticated roles has the permissions granted by the
    // user_test_feature feature.
    $this->assertTrue(in_array('cancel account', $auth_perms));
    $this->assertTrue(in_array('change own username', $auth_perms));
    $this->assertTrue(in_array('select account cancellation method', $auth_perms));
  }

}
