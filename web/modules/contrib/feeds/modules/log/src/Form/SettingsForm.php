<?php

namespace Drupal\feeds_log\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\feeds_log\LogFileManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure feeds_log settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The feeds log file manager.
   *
   * @var \Drupal\feeds_log\LogFileManagerInterface
   */
  protected $logFileManager;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\feeds_log\LogFileManagerInterface $log_file_manager
   *   The feeds log file manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, LogFileManagerInterface $log_file_manager) {
    parent::__construct($config_factory);
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->logFileManager = $log_file_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('feeds_log.file_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_log_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['feeds_log.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('feeds_log.settings');
    $form['age_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of seconds to keep logged imports'),
      '#description' => $this->t('The maximum time in seconds to keep logged imports. If you run a lot of imports frequently you may want to set this to a lower amount. Set to 0 to keep all logs (not recommended). Requires a <a href=":cron">cron maintenance task</a>.', [
        ':cron' => Url::fromRoute('system.status')->toString(),
      ]),
      '#default_value' => $config->get('age_limit'),
    ];
    $form['log_dir'] = [
      '#type' => 'feeds_uri',
      '#title' => $this->t('Feeds log directory'),
      '#description' => $this->t('Directory where logged files get stored. Prefix the path with a scheme. Available schemes: @schemes.', ['@schemes' => implode(', ', $this->getSchemes())]),
      '#default_value' => !empty($config->get('log_dir')) ? $config->get('log_dir') : $this->logFileManager->getFeedsLogDirectory(),
      '#allowed_schemes' => $this->getSchemes(),
    ];

    $form['stampede'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Import log overload protection'),
      'max_amount' => [
        '#type' => 'number',
        '#title' => $this->t('Max amount'),
        '#description' => $this->t('The maximum number of allowed logs for a single feed within a certain timeframe.'),
        '#default_value' => $config->get('stampede')['max_amount'],
      ],
      'age' => [
        '#type' => 'number',
        '#title' => $this->t('Age'),
        '#description' => $this->t('The maximum time in seconds to look back for detecting an import log overload.'),
        '#default_value' => $config->get('stampede')['age'],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('feeds_log.settings')
      ->set('age_limit', $form_state->getValue('age_limit'))
      ->set('log_dir', $form_state->getValue('log_dir'))
      ->set('stampede', $form_state->getValue('stampede'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns available schemes.
   *
   * @return string[]
   *   The available schemes.
   */
  protected function getSchemes() {
    return array_keys($this->streamWrapperManager->getWrappers(StreamWrapperInterface::WRITE_VISIBLE));
  }

}
