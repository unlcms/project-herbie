<?php

namespace Drupal\unl_multisite\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Site deletion confirmation.
 */
class UnlMultisiteDelete extends ConfirmFormBase {

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
    return 'unl_multisite_site_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the site?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('unl_multisite.site_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $site_id = NULL) {
    $site_path = $this->databaseConnection->select('unl_sites', 's')
      ->fields('s', array('site_path'))
      ->condition('site_id', $site_id)
      ->execute()
      ->fetchCol();

    $form['site_id'] = array(
      '#type' => 'value',
      '#value' => $site_id,
    );
    $form['confirm_delete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Confirm'),
      '#description' => $this->getQuestion(),
      '#required' => TRUE,
    );
    $form['confirm_again'] = array(
      '#type' => 'checkbox',
      '#title' => t('Confirm again'),
      '#description' => t('I am sure I want to permanently delete the site at: %site_path', array('%site_path' => $site_path[0])),
      '#required' => TRUE,
    );

    $form['description'] = ['#markup' => $this->getDescription()];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!isset($values['site_id'])) {
      return;
    }
    $this->flagSiteToRemove($values['site_id']);
    $this->messenger->addStatus(t('The site has been scheduled for removal.'));
    $form_state->setRedirect('unl_multisite.site_list');
  }

  private function flagSiteToRemove($site_id) {
    $this->databaseConnection->update('unl_sites')
      ->fields(array('installed' => 3))
      ->condition('site_id', $site_id)
      ->execute();
    $this->databaseConnection->update('unl_sites_aliases')
      ->fields(array('installed' => 3))
      ->condition('site_id', $site_id)
      ->execute();

    return TRUE;
  }

}
