<?php

namespace Drupal\unl_user;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap;

/**
 * Query unl user data
 */
class PersonDataQuery {
  const SOURCE_LDAP = 'ldap';
  const SOURCE_DIRECTORY = 'directory.unl.edu';

  function __construct() {
    // Nothing to do here.
  }

  public function getUserData($username) {
    $result = false;
    $config = \Drupal::config('unl_user.settings');

    // First, try getting the info from LDAP.
    try {
      // Pick which field we are querying based on username_format setting.
      $query_field = 'employeeID'; // NUID
      if (empty($config->get('username_format')) || $config->get('username_format') == 'myunl') {
        $query_field = 'sAMAccountName'; // My.UNL
      }

      $client = $this->getClient();
      $query = $client->query('ou=people,dc=unl,dc=edu', $query_field . '=' . $client->escape($username));
      $results = $query->execute();
      if (count($results) > 0) {
        $result = $results[0]->getAttributes();
        $result['data-source'] = self::SOURCE_LDAP;
      }
    }
    catch (\Exception $e) {
      // Don't do anything, just go on to try the Directory method.
    }

    // Next, if LDAP didn't work, try Directory service.
    if (!$result && $config->get('username_format') == 'myunl') {
      $json = file_get_contents('https://directory.unl.edu/service.php?format=json&uid=' . $username);
      if ($json) {
        $result = json_decode($json, TRUE);
        $result['data-source'] = self::SOURCE_DIRECTORY;
      }
    }

    if ($result) {
      return $this->sanitizeUserRecordData($result);
    }

    // Return the false value.
    return $result;
  }

  public function search($search) {
    $results = [];

    try {
      $client = $this->getClient();

      $searchFields = array('sAMAccountName', 'mail', 'cn', 'givenName', 'sn', 'displayName', 'employeeID');

      foreach (preg_split('/\s+/', $search) as $searchTerm) {
        $searchTerm = $client->escape($searchTerm);
        $filter .= '(|';
        foreach ($searchFields as $searchField) {
          $filter .= '(' . $searchField . '=*' . $searchTerm . '*)';
        }
        $filter .= ')';
      }

      $query = $client->query('ou=people,dc=unl,dc=edu', $filter);
      $tmp_results = $query->execute();
      foreach ($tmp_results as $result) {
        //We want the attributes array
        $result = $result->getAttributes();
        //Mark the result as LDAP
        $result['data-source'] = self::SOURCE_LDAP;
        $results[] = $result;
      }
    } catch (\Exception $e) {
      // There was a problem, fetch with Directory instead.
      $results = json_decode(file_get_contents('https://directory.unl.edu/service.php?q='.urlencode($search).'&format=json&method=getLikeMatches'), TRUE);
      foreach($results as $key=>$value) {
        $results[$key]['data-source'] = self::SOURCE_DIRECTORY;
      }
    }

    // Clean up data.
    $clean_results = [];
    foreach ($results as $key => $result) {
      $result = $this->sanitizeUserRecordData($result);

      if (!empty($result['uid'])) {
        // Skip any records missing a UID.
        $clean_results[] = $result;
      }
    }

    return $clean_results;
  }

  /**
   * @return Ldap
   * @throws \Exception
   */
  protected function getClient() {
    static $client;

    if ($client !== null) {
      return $client;
    }

    $config = \Drupal::config('unl_user.settings');

    if (empty($config->get('dn'))) {
      throw new \Exception('the LDAP DN is not set, we will be unable to connect to LDAP');
    }

    if (empty($config->get('password'))) {
      throw new \Exception('the LDAP password is not set, we will be unable to connect to LDAP');
    }

    if (empty($config->get('uri'))) {
      throw new \Exception('the LDAP uri is not set, we will be unable to connect to LDAP');
    }

    $adapter = new Adapter([
      'connection_string' => $config->get('uri'),
      'version' => 3,
    ]);
    $client = new Ldap($adapter);
    $client->bind($config->get('dn'), $config->get('password'));

    return $client;
  }

  /**
   * Sanitize a user record data that was retrieved from either LDAP or Directory.
   *
   * @param array $data
   *
   * @return array
   */
  public function sanitizeUserRecordData(array $data) {
    $config = \Drupal::config('unl_user.settings');

    $userData = [
      'uid'     => '',
      'mail'     => '',
      'data'     => [
        'unl' => [],
      ],
    ];

    // If either LDAP or Directory found data, use it.
    if (!empty($data)) {
      $userData['data']['unl'] = [
        'source' => $data['data-source'],

        'nuid' => $data['employeeID'][0] ?: $data['unluncwid'][0], // NUID (12345678)
        'unl_uid'             => $data['cn'][0],                   // My.UNL ID (hhusker1)

        'displayName'         => $data['displayName'][0],          // Full Name
        'givenName'           => $data['givenName'][0],            // First Name
        'sn'                  => $data['sn'][0],                   // Last Name

        'departmentNumber'     => $data['departmentNumber'][0] ?: '', // Org unit for staff/faculty
        'memberOf'             => $data['memberOf'] ?: [],         // Grouper groups
        'eduPersonAffiliation' => $data['eduPersonAffiliation'],   // Array of all affiliations
        'eduPersonPrimaryAffiliation' => $data['eduPersonPrimaryAffiliation'][0], // Primary affiliation
      ];

      if (empty($config->get('username_format')) || $config->get('username_format') == 'myunl') {
        $userData['uid'] = $userData['data']['unl']['unl_uid'];
      }
      else {
        $userData['uid'] = $userData['data']['unl']['nuid'];
      }

      if ($data['mail'][0]) {
        $userData['mail'] = $data['mail'][0];
      }
      else {
        // No email found, use a default one.
        $userData['mail'] = $userData['data']['unl']['unl_uid'].'+UNLCMS@unl.edu';
      }
    }

    return $userData;
  }
}
