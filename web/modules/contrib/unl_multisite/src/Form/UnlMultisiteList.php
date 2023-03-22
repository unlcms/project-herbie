<?php

namespace Drupal\unl_multisite\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure book settings for this site.
 */
class UnlMultisiteList extends FormBase {

  /**
   * Base database API.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * Request represents an HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Class constructor for form object.
   *
   * @param \Drupal\Core\Database\Connection $database_connection
   *   Base database API.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack that controls the lifecycle of requests.
   */
  public function __construct(Connection $database_connection, RequestStack $request) {
    $this->databaseConnection = $database_connection;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_multisite_site_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header = array(
      'uri' => array(
        'data' => t('Default path'),
        'field' => 'site_path',
      ),
      'name' => array(
        'data' => t('Site name'),
        'field' => 'name',
      ),
      'access' =>  array(
        'data' => t('Last access'),
        'field' => 'access',
      ),
      'installed' => array(
        'data' => t('Status'),
        'field' => 'installed',
      ),
      'operations' => t('Operations'),
    );

    $sites = $this->databaseConnection->select('unl_sites', 's')
      ->fields('s', array('site_id', 'site_path', 'uri', 'installed'))
      ->execute()
      ->fetchAll();

    // In addition to the above db query, add site name and last access timestamp
    $this->unl_add_extra_site_info($sites);

    $form['unl_sites'] = array(
      '#caption' => t('Existing Sites: ') . count($sites),
      '#type' => 'table',
      '#header' => $header,
      '#empty' => t('No sites have been created.'),
    );

    foreach ($sites as $site) {
      $rows[$site->site_id] = array(
        'uri' => array(
          '#type' => 'link',
          '#title' => $site->site_path,
          '#url' => Url::fromUserInput('/' . $site->site_path),
        ),
        'name' => array('#plain_text' => (isset($site->name) ? $site->name : '')),
        'access' => array('#plain_text' => (isset($site->access) ? $site->access : 0)),
        'installed' => array('#plain_text' => $this->_unl_get_install_status_text($site->installed)),
        'operations' => array(
          'data' => array(
            '#type' => 'operations',
            '#links' => array(
              'aliases_create' => array(
                'title' => t('create alias'),
                'url' => Url::fromRoute('unl_multisite.site_aliases_create', ['site_id' => $site->site_id]),
              ),
              'aliases' => array(
                'title' => t('view aliases'),
                'url' => Url::fromRoute('unl_multisite.site_aliases', ['site_id' => $site->site_id]),
              ),
              'edit' => array(
                'title' => t('edit site'),
                'url' => Url::fromRoute('unl_multisite.site_list', array()),//'admin/sites/unl/' . $site->site_id . '/edit',
              ),
              'delete' => array(
                'title' => t('delete site'),
                'url' => Url::fromRoute('unl_multisite.site_delete', ['site_id' => $site->site_id]),
              ),
            ),
          ),
        ),
      );
    }

    // Sort the table data accordingly with a custom sort function
    $order = TableSort::getOrder($header, $this->request);
    $sort = TableSort::getOrder($header, $this->request);
    $rows = $this->unl_sites_sort($rows, $order, $sort);

    // Now that the access timestamp has been used to sort, convert it to something readable
    foreach ($rows as $key=>$row) {
      $rows[$key]['access'] = array('#plain_text' =>
        isset($row['access']) && $row['access']['#plain_text'] > 0
          ? t('@time ago', array('@time' => \Drupal::service("date.formatter")->formatInterval(REQUEST_TIME - $row['access']['#plain_text'])))
          : t('never')
      );
    }

    foreach ($rows as $key => $row) {
      $form['unl_sites'][$key] = $row;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return;
  }

  /**
   * Adds virtual name and access fields to a result set from the unl_sites table.
   * @param $sites The result of $this->databaseConnection->select()->fetchAll() on the unl_sites table.
   */
  function unl_add_extra_site_info(&$sites) {
    // Get all custom made roles (roles other than authenticated, anonymous, administrator)
    $roles = user_roles(TRUE);
    unset($roles[\Drupal\Core\Session\AccountInterface::AUTHENTICATED_ROLE]);
    unset($roles['administrator']);

    foreach ($sites as &$row) {
      // Skip over any sites that aren't properly installed.
      if (!in_array($row->installed, array(2, 6))) {
        continue;
      }

      // getenv('HOME') seems to be unset in some configurations when running drush
      // via shell_exec(). This cause Webmozart\PathUtil\Path\getHomeDirectory() to fail.
      // Set a value here to force it to work.
      putenv("HOME=".DRUPAL_ROOT);
      $command = DRUPAL_ROOT . "/../vendor/drush/drush/drush -y --uri={$row->uri} config:get system.site name --format";
      $name = shell_exec($command);
      if (stripos($name, 'Drush command terminated abnormally') !== FALSE) {
        throw new Exception('Error while fetching site names.');
      }

      // Get last access timestamp (by a non-administrator)
      if (!empty($roles)) {
        // Same as the problem above. This isn't the best way to run drush from a module.
        $path = getenv('PATH');
        putenv("PATH={$path}:/usr/local/mysql/bin");

        $table_users = 'users_field_data u';
        $table_users_roles = 'user__roles r';
        $query = 'SELECT u.access FROM '.$table_users.', '.$table_users_roles.' WHERE u.uid = r.entity_id AND u.access > 0 AND r.roles_target_id IN (' . "'".implode("','", array_keys($roles))."'" . ') ORDER BY u.access DESC';
        $command = DRUPAL_ROOT . "/../vendor/drush/drush/drush -y --uri={$row->uri} sql:query \"{$query}\"";
        $access = shell_exec($command);
        if (stripos($access, 'Drush command terminated abnormally') !== FALSE) {
          throw new Exception('Error while fetching access times.');
        }
      }
      else {
        $access = 0;
      }

      $row->name = $name;
      $row->access = (int)$access;
    }
  }

  /**
   * Custom sort the Existing Sites table.
   */
  private function unl_sites_sort($rows, $order, $sort) {
    switch ($order['sql']) {
      case 'uri':
        if ($sort == 'asc') {
          usort($rows, function ($a, $b) {return strcasecmp($a['uri']['#title'], $b['uri']['#title']);});
        }
        else {
          usort($rows, function ($a, $b) {return strcasecmp($b['uri']['#title'], $a['uri']['#title']);});
        }
        break;
      case 'name':
        if ($sort == 'asc') {
          usort($rows, function ($a, $b) {return strcasecmp($a['name'], $b['name']);});
        }
        else {
          usort($rows, function ($a, $b) {return strcasecmp($b['name'], $a['name']);});
        }
        break;
      case 'access':
        if ($sort == 'asc') {
          usort($rows, function ($a, $b) {return strcmp($b['access'], $a['access']);});
        }
        else {
          usort($rows, function ($a, $b) {return strcmp($a['access'], $b['access']);});
        }
        break;
      case 'last_update':
        if ($sort == 'asc') {
          usort($rows, function ($a, $b) {return strcmp($b['last_update'], $a['last_update']);});
        }
        else {
          usort($rows, function ($a, $b) {return strcmp($a['last_update'], $b['last_update']);});
        }
        break;
      case 'installed':
        if ($sort == 'asc') {
          usort($rows, function ($a, $b) {return strcmp($a['installed'], $b['installed']);});
        }
        else {
          usort($rows, function ($a, $b) {return strcmp($b['installed'], $a['installed']);});
        }
        break;
    }
    return $rows;
  }

  public static function _unl_get_install_status_text($id) {
    switch ($id) {
      case 0:
        $installed = t('Scheduled for creation.');
        break;
      case 1:
        $installed = t('Currently being created.');
        break;
      case 2:
        $installed = t('In production.');
        break;
      case 3:
        $installed = t('Scheduled for removal.');
        break;
      case 4:
        $installed = t('Currently being removed.');
        break;
      case 5:
        $installed = t('Failure/Unknown.');
        break;
      case 6:
        $installed = t('Scheduled for site update.');
        break;
      default:
        $installed = t('Unknown');
        break;
    }
    return $installed;
  }

}
