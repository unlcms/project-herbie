<?php

namespace Drupal\unl_multisite\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure book settings for this site.
 */
class UnlMultisiteAdd extends FormBase {

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
    return 'unl_multisite_site_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['site_path'] = array(
      '#type' => 'textfield',
      '#title' => t('New site path'),
      '#description' => t('Relative url for the new site.'),
      '#default_value' => 'newsite',
      '#required' => TRUE,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Create site'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('site_path', $this->validatePath($form, $form_state));

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_path = $form_state->getValue('site_path');

    // Sanitize submitted URLs
    $site_path = explode('/', $site_path);
    foreach ($site_path as $key => $url_part) {
      $url_part = strtolower($url_part);
      $url_part = preg_replace('/[^a-z0-9]/', '-', $url_part);
      $url_part = preg_replace('/-+/', '-', $url_part);
      $url_part = preg_replace('/(^-)|(-$)/', '', $url_part);
      $site_path[$key] = $url_part;
    }
    $site_path = implode('/', $site_path);

    $uri = Url::fromUserInput('/'.$site_path, array('absolute' => TRUE, 'https' => FALSE))->toString();

    $id = $this->databaseConnection->insert('unl_sites')
      ->fields(array(
        'site_path' => $site_path,
        'uri' => $uri,
      ))
      ->execute();

    $this->messenger->addStatus(t('The site @uri has been scheduled for creation. Run unl_multisite/cron.php to finish install.', array('@uri' => $uri)));

    $url = \Drupal\Core\Url::fromRoute('unl_multisite.site_list');
    return $form_state->setRedirectUrl($url);
  }

  /**
   * Custom function to validate and correct a path submitted in a form.
   */
  function validatePath(array $form, FormStateInterface $form_state) {
    $site_path = trim($form_state->getValue('site_path'));

    if (substr($site_path, 0, 1) == '/') {
      $site_path = substr($site_path, 1);
    }
    if (substr($site_path, -1) != '/') {
      $site_path .= '/';
    }

    $site_path_parts = explode('/', $site_path);
    $first_directory = array_shift($site_path_parts);
    if (in_array($first_directory, array('core', 'includes', 'misc', 'modules', 'profiles', 'scripts', 'sites', 'themes', 'vendor'))) {
      $form_state->setErrorByName('site_path', t('Drupal site paths must not start with the @first_directory directory.', array('@first_directory' => $first_directory)));
    }

    if ($form['#form_id'] != 'unl_site_create') {
      if (substr(strtolower($form['site_path']['#default_value']), 0, strlen($site_path)) ==  strtolower($site_path)) {
        $form_state->setErrorByName('site_path', t('New path cannot be parent directory of current path.'));
      }

      if (substr(strtolower($site_path), 0, strlen($form['site_path']['#default_value'])) ==  strtolower($form['site_path']['#default_value'])) {
        $form_state->setErrorByName('site_path', t('New path cannot be sub directory of current path.'));
      }
    }

    $site = $this->databaseConnection->select('unl_sites', 's')
      ->fields('s', array('site_path'))
      ->condition('site_path', $site_path)
      ->execute()
      ->fetch();

    $alias = $this->databaseConnection->select('unl_sites_aliases', 'a')
      ->fields('a', array('path'))
      ->condition('path', $site_path)
      ->execute()
      ->fetch();

    if ($site || $alias) {
      $form_state->setErrorByName('site_path', t('Path already in use.'));
    }

    return $site_path;
  }

}
