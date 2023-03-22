# UNL_USER module

This module does the following
* allows searching and importing users into the system
* stores UNL information, such as the 'eduPersonAffiliation', to help with access restrictions

## Why LDAP as a source of user information instead of directory.unl.edu?
LDAP gives us access to users that have their privacy flag set. This is important so that we can add any users to sites regardless of weather or not their privacy flag is set.

Note that we use directory.unl.edu as a backup source of information in the case that either LDAP is down or the LDAP credentials are not provides/bad.


## Getting and setting UNL user data
UNL Specific user data is retrieved when an account is first created. Every login there-after will trigger an update, but it may not happen right away.

To manually update user data:
```
$helper = new Helper();
$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
$helper->updateUserData($user);
```

To retrieve user data
```
/**
* @var UserDataInterface $userDataService
*/
$userDataService = \Drupal::service('user.data');

//Specific field
$primaryAffiliation = $userDataService->get('unl_cas', \Drupal::currentUser()->id(), 'primaryAffiliation');

//All UNL user data
$allUserData = $userDataService->get('unl_cas', \Drupal::currentUser()->id());

```

## Testing
Tests are built in and can be run with the core `run-tests.sh` script

```
php ./core/scripts/run-tests.sh --module unl_user --verbose --url "http://your-base-url.com/unlcms2/"
```
