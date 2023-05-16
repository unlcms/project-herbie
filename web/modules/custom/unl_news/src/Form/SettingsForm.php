<?php

namespace Drupal\unl_news\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The settings form for the UNL News module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'unl_news.settings';

  /**
   * Queue name.
   *
   * @var string
   */
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
   * A GuzzleHTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The default cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A GuzzleHTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(MessengerInterface $messenger, QueueFactory $queue_factory, ClientInterface $http_client, CacheBackendInterface $cache, TimeInterface $time, LoggerInterface $logger) {
    $this->messenger = $messenger;
    $this->queueFactory = $queue_factory;
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->time = $time;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('queue'),
      $container->get('http_client'),
      $container->get('cache.default'),
      $container->get('datetime.time'),
      $container->get('logger.channel.unl_news'),
    );
  }

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

    // Load tags from cache.
    $cache = $this->cache->get('unl_news.ne_today_tags');
    if ($cache) {
      $options = $cache->data;
    }
    // If tags are not cached, then attempt to refresh.
    else {
      if ($this->tagsRefresh()) {
        $cache = $this->cache->get('unl_news.ne_today_tags');
        $options = $cache->data;
      }
      else {
        $options = [];
        $tag_ids_disabled = TRUE;
      }
    }

    $form['tags'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tags'),
    ];

    $current_tags = [];
    foreach ($config->get('tag_ids') as $id) {
      $current_tags[] = '<span class="unl-news-tag">'.$options[$id].'</span>';
    }
    $tag_text = 'None';
    if (!empty($current_tags)) {
      $tag_text = implode(', ', $current_tags);
    }
    $form['tags']['current_tags'] = [
      '#markup' => '<p><strong>'.$this->t('Currently selected tags:').' </strong>'.$tag_text.'</p>',
    ];

    $form['tags']['tag_ids'] = [
      '#type' => 'select',
      '#title' => $this->t('Available tags'),
      '#description' => $this->t('Articles in Nebraska Today with the selected tags will be imported into this site.'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $config->get('tag_ids'),
      '#disabled' => FALSE,
    ];
    if (isset($tag_ids_disabled) && $tag_ids_disabled) {
      $form['tags']['tag_ids']['#disabled'] = TRUE;
      $form['tags']['tag_ids']['#description'] = $this->t('<strong>Unable to retrieve tags from Nebraska Today API. This field is disabled to prevent data loss.</strong><br>Nebraska Today tags to be imported by this site.');
    }

    $form['tags']['tags_refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh tags'),
      '#name' => 'tags-refresh',
    ];

    $form['tags']['tags_refresh_description'] = [
      '#markup' => $this->t('Download an updated tags list from Nebraska Today.'),
    ];

    $form['retrieve_articles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Retrieve articles'),
    ];

    $form['retrieve_articles']['markup'] = [
      '#markup' => $this->t('<p>Articles are retrieved from the Nebraska Today API and then queued by cron.</p>'),
    ];

    $form['retrieve_articles']['manual_api_pull'] = [
      '#type' => 'submit',
      '#value' => $this->t('Retrieve items from API'),
      '#name' => 'manual-api-pull',
    ];

    $form['retrieve_articles']['manual_batch_description'] = [
      '#markup' => $this->t('<p class="description">When manually retrieving items, do not close your browser window until completion.</p>'),
    ];

    $form['queue'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Import queue'),
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
    if (in_array($form_state->getTriggeringElement()['#name'], [
      'tags-refresh',
      'manual-batch',
      'manual-api-pull',
    ])) {

      return;
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement()['#name'];
    if ($triggering_element == 'tags-refresh') {
      $this->tagsRefresh();
      return;
    }
    elseif ($triggering_element == 'manual-batch') {
      $this->manualBatch();
      return;
    }
    elseif ($triggering_element == 'manual-api-pull') {
      unl_news_cron();
      return;
    }

    $config = $this->configFactory->getEditable(static::SETTINGS);

    if (!$form['tag_ids']['#disabled']) {
      $tag_ids = $form_state->getValue('tag_ids');
      $tag_ids = array_keys($tag_ids);

      $config->set('tag_ids', $tag_ids);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Initiates batch processing with queue_ui.batch service.
   */
  public function manualBatch() {
    \Drupal::service('queue_ui.batch')->batch([self::QUEUE_NAME]);
  }

  /**
   * Downloads an updated copy of tags from Nebraska Today API.
   *
   * @return bool
   *   TRUE is successful; False if unsuccessful.
   */
  public function tagsRefresh() {
    try {
      $request = $this->httpClient->get('https://news.unl.edu/api/v1/tags?format=json');
      $json_string = (string) $request->getBody();
      $response_payload = json_decode($json_string);
      $options = [];
      foreach ($response_payload->data as $term) {
        $options[$term->id] = $term->name;
      }
      asort($options, SORT_STRING | SORT_FLAG_CASE);

      // Cache permanently.
      $this->cache->set('unl_news.ne_today_tags', $options);
      $this->messenger->addMessage('Tags successfully refreshed from Nebraska Today API.');
      return TRUE;
    }
    catch (GuzzleException $e) {
      $message = 'Guzzle exception: ' . get_class($e) . '. Error message: ' . $e->getMessage();
      $this->logger->error($message);
      $this->messenger->addError('Unable to retrieve tags from Nebraska Today API.');
      return FALSE;
    }
  }

}
