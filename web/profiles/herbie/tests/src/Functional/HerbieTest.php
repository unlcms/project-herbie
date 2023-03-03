<?php

namespace Drupal\Tests\herbie\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;
use Drupal\user\UserInterface;

/**
 * Tests Herbie installation profile expectations.
 *
 * @group herbie
 */
class HerbieTest extends BrowserTestBase {

  use RequirementsPageTrait;

  protected $profile = 'herbie';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'unl_five_herbie';

  /**
   * Tests Herbie installation profile.
   */
  public function testHerbie() {
    $this->drupalGet('');
    
    // Create a user to test tools and navigation blocks for logged in users
    // with appropriate permissions.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Tools');
    $this->assertSession()->pageTextContains('Administration');

    // Ensure that there are no pending updates after installation.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('update.php/selection');
    $this->updateRequirementsProblem();
    $this->drupalGet('update.php/selection');
    $this->assertSession()->pageTextContains('No pending updates.');

    // Ensure that there are no pending entity updates after installation.
    $this->assertFalse($this->container->get('entity.definition_update_manager')->needsUpdates(), 'After installation, entity schema is up to date.');

    // Ensure special configuration overrides are correct.
    $this->assertFalse($this->config('system.theme.global')->get('features.node_user_picture'), 'Configuration system.theme.global:features.node_user_picture is FALSE.');
    $this->assertEquals(UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL, $this->config('user.settings')->get('register'));
  }

}
