<?php

namespace Drupal\unl_user;

use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;

/**
 * Helper functions for unl_user.
 */
class Helper {

  function __construct() {
    // Nothing to do here.
  }

  /**
   * Import a UNL user into the system and return the Drupal User object.
   *
   * @param $username
   *
   * @return bool|\Drupal\user\Entity\User
   */
  public function initializeUser($username) {
    $username = trim($username);
    $user = user_load_by_name($username);
    if (!$user) {
      $user = User::create();
      $user->setUsername($username);
      $user->setEmail($username . '@unl.edu');
      $user->enforceIsNew();
      $user->activate();
      $user->save();

      // If using the CAS module, set the "Allow user to log in via CAS" setting.
      if (\Drupal::moduleHandler()->moduleExists('cas')) {
        $externalauth = \Drupal::service('externalauth.externalauth');
        $externalauth->linkExistingAccount($username, 'cas', $user);
      }

      // Initialize users_data values.
      $this->updateUserData($user);
    }

    return $user;
  }

  /**
   * Update custom UNL specific user data for a given user.
   *
   * Updating a user would look something like this:
   * $helper = new Helper();
   * $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
   * $helper->updateUserData($user);
   *
   * @param User $user
   *
   * @return bool
   */
  public function updateUserData(User $user) {
    $query = new PersonDataQuery();
    $data = $query->getUserData($user->getAccountName());

    if (!$data) {
      //No data to be found
      return false;
    }

    // Update the email address.
    $user->setEmail($data['mail']);
    $user->save();

    /**
     * @var UserDataInterface $userDataService
     */
    $userDataService = \Drupal::service('user.data');

    foreach ($data['data']['unl'] as $key=>$value) {
      $userDataService->set('unl_user', $user->id(), $key, $value);
    }

    $userDataService->set('unl_user', $user->id(), 'last-update', time());

    return true;
  }
}
