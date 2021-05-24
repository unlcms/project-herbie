<?php

namespace Drupal\unl_news\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The settings form for the UNL News module.
 */
class SettingsForm extends ConfigFormBase {

  const QUEUE_NAME = 'nebraska_today_queue_processor';

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   */
  public function __construct(MessengerInterface $messenger, QueueFactory $queue_factory) {
    $this->messenger = $messenger;
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('queue')
    );
  }

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'unl_news.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_news_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // Temp until Nebraska Today API is updated.
    $options = [
      '20861' => 'Riko Bishop',
      '31417' => '2021 spring commencement',
      '119' => 'Tom Osborne',
      '520' => 'graduation',
      '17' => 'commencement',
      '2795' => 'Jennifer Clark',
      '32877' => 'Connie Clifton Rath',
      '15922' => 'Grit and Glory',
    ];
    asort($options, SORT_STRING | SORT_FLAG_CASE);

    $form['tag_ids'] = [
      '#type' => 'select',
      '#title' => $this->t('Tags'),
      '#description' => $this->t('Nebraska Today tags to be imported by this site.'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $config->get('tag_ids'),
    ];

    $form['queue'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Import Queue'),
    ];

    /** @var \Drupal\Core\Queue\QueueInterface */
    $queue = $this->queueFactory->get(self::QUEUE_NAME);

    $form['queue']['queue_number_of_items'] = [
      '#markup' => $this->t('<p><strong>Number of items in queue:</strong> @number.</p><p>Queue items are processed by cron. Depending on how many items are in queue, it may take several cron jobs cycles to process all of the items.</p>', ['@number' => $queue->numberOfItems()]),
    ];

    $form['queue']['manual_batch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process remaining items'),
      '#name' => 'manual-batch',
    ];

    $form['queue']['manual_batch_description'] = [
      '#markup' => $this->t('<p class="description">When manually processing items, do not close your browser window until completion.</p>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'manual-batch') {
      return;
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'manual-batch') {
      $this->manualBatch();
      return;
    }
    $tag_ids = $form_state->getValue('tag_ids');
    $tag_ids = array_keys($tag_ids);

    $config = $this->configFactory->getEditable(static::SETTINGS)
      ->set('tag_ids', $tag_ids);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Initiates batch processing with Batch API.
   *
   * Replace with service when/if
   * https://www.drupal.org/project/queue_ui/issues/3214399
   * is committed.
   */
  public function manualBatch() {
    $batch = [
      'title' => $this->t('Processing queues'),
      'operations' => [],
      'finished' => ['\Drupal\queue_ui\QueueUIBatch', 'finish'],
    ];

    $batch['operations'][] = ['\Drupal\queue_ui\QueueUIBatch::step', [self::QUEUE_NAME]];

    batch_set($batch);
  }

}
