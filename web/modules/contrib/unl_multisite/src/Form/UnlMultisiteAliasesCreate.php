<?php

namespace Drupal\unl_multisite\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UnlMultisiteAliasesCreate extends FormBase {

  /**
   * Base database API.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Class constructor for form object.
   *
   * @param \Drupal\Core\Database\Connection $database_connection
   *   Base database API.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(Connection $database_connection, Messenger $messenger) {
    $this->databaseConnection = $database_connection;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_multisite_site_aliases_create';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $site_id = NULL) {
    $query = $this->databaseConnection->select('unl_sites', 's')
      ->fields('s', array('site_id', 'site_path'))
      ->orderBy('uri');
    if (isset($site_id)) {
      $query->condition('site_id', $site_id);
    }
    $sites = $query->execute()->fetchAll();
    foreach ($sites as $site) {
      $site_list[$site->site_id] = $site->site_path;
    }

    $form['site'] = array(
      '#type' => 'select',
      '#title' => t('Aliased site path'),
      '#description' => t('The site the alias will point to.'),
      '#options' => $site_list,
      '#required' => TRUE,
      '#default_value' => (isset($site_id) ? $site_id : FALSE),
      '#disabled' => (isset($site_id) ? TRUE : FALSE),
    );
    $form['base_uri'] = array(
      '#type' => 'textfield',
      '#title' => t('Alias base URL'),
      '#description' => t('The base URL for the new alias. This should resolve to the directory containing the .htaccess file.'),
      '#default_value' => Url::fromRoute('<front>', [], ['https' => FALSE, 'absolute' => TRUE])->toString(),
      '#required' => TRUE,
    );
    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#description' => t('Path for the new alias.'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Create alias'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('base_uri', trim($form_state->getValue('base_uri')));
    $form_state->setValue('path', trim($form_state->getValue('path')));

    if (substr($form_state->getValue('base_uri'), -1) != '/') {
      $form_state->setValue('base_uri', $form_state->getValue('base_uri') . '/');
    }
    if (substr($form_state->getValue('path'), -1) != '/') {
      $form_state->setValue('path', $form_state->getValue('path') . '/');
    }
    if (substr($form_state->getValue('path'), 0, 1) == '/') {
      $form_state->setValue('path', substr($form_state->getValue('path'), 1));
    }

    // Check that the alias does not already exist.
    $query = $this->databaseConnection->select('unl_sites_aliases', 'a');
    $query->fields('a', array('base_uri', 'path'));

    $db_or = new Condition('OR');
    $db_or->condition('a.path', $form_state->getValue('path'), '=');

    // Also consider legacy aliases that do not have a trailing slash.
    $db_or->condition('a.path', substr($form_state->getValue('path'), 0, -1), '=');

    $db_and = new Condition('AND');
    $db_and->condition('a.base_uri', $form_state->getValue('base_uri'), '=');
    $db_and->condition($db_or);

    $query->condition($db_and);
    $result = $query->execute()->fetchAssoc();

    if ($result) {
      form_set_error('alias_path', t('Site alias already exists.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->databaseConnection->insert('unl_sites_aliases')->fields(array(
      'site_id' => $form_state->getValue('site'),
      'base_uri' => $form_state->getValue('base_uri'),
      'path' => $form_state->getValue('path'),
    ))->execute();

    $this->messenger->addStatus(t('The site alias has been scheduled for creation. Run unl_multisite/cron.php to finish creation.'));
  }

}
