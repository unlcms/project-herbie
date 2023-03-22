# UNL CAS 2.x

The previous version of unl_cas handled the CAS authentication: https://github.com/unlcms/unl_cas/tree/8.x-1.x/

This version uses the main Drupal community "cas" module for authentication. This module now only makes UI modifications.

Logic for importing users and user data lives in its own module, unl_user.
 
The redirect behavior executed at '/user/login' can be disabled by setting an environment variable: 'UNLCAS_BYPASS_LOGIN_REDIRECT'.
