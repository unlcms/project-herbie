<?php

namespace Drupal\unl_news\Plugin\QueueWorker;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\Token;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Node Tasks.
 *
 * @QueueWorker(
 *   id = "nebraska_today_queue_processor",
 *   title = @Translation("Task Worker: Nebraska Today Articles"),
 *   cron = {"time" = 20}
 * )
 */
class NebraskaTodayQueueProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * A GuzzleHTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Drupal placeholder/token replacement system.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Drupal's state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \GuzzleHttp\ClientInterface $client
   *   A GuzzleHTTP client.
   * @param \Drupal\Core\Utility\Token $token
   *   The public stream wrapper.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal's state system.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, ClientInterface $client, Token $token, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->client = $client;
    $this->token = $token;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('token'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $node = Node::create([
      'type' => 'news',
    ]);
    // Truncate title to 255 characters as is allowed by the
    // node title field.
    $title = Unicode::truncate($item->title, 255, TRUE, TRUE);
    $node->set('title', $title);
    $node->set('n_news_foreign_key', $item->id);
    $node->set('n_news_canonical_url', $item->canonicalUrl);

    // Expect ISO8601 date.
    // Convert incoming timestamp to local timezone and then into a
    // UNIX timestamp.
    $pub_date = new \DateTime($item->pubDate);
    $default_system_timezone = $this->configFactory->get('system.date')->get('timezone')['default'];
    $default_system_timezone = new \DateTimeZone($default_system_timezone);
    $pub_date = $pub_date->setTimezone($default_system_timezone);
    $pub_date = $pub_date->format('U');
    $node->set('created', $pub_date);

    if (isset($item->articleImage)) {
      // Due to an API bug, non images (e.g. video links) are
      // sometimes returned.
      if (stripos($item->articleImage->mimeType, 'image/') === 0) {
        $remote_image_url = $item->articleImage->url;
        $filename = explode('/', $remote_image_url);
        $filename = end($filename);

        // Truncate alt text to 512 characters as is allowed by the
        // n_news_image_alt field.
        $alt = Unicode::truncate($item->articleImage->alt, 512, TRUE, TRUE);

        // Get image upload path from field config.
        /** @var \Drupal\field\Entity\FieldConfig */
        $img_field_config = FieldConfig::loadByName('node', 'news', 'n_news_image');
        $img_dir = $img_field_config->getSetting('file_directory');
        $img_dir = $this->token->replace($img_dir);
        $img_dir = 'public://' . $img_dir;

        $this->fileSystem->prepareDirectory($img_dir, $this->fileSystem::CREATE_DIRECTORY);

        $local_image_uri = $img_dir . '/' . $filename;
        // Download the file with GuzzleHTTP.
        $this->client->request('GET', $remote_image_url, ['sink' => $local_image_uri]);

        $file = File::create([
          'uri' => $local_image_uri,
        ]);
        $file->setPermanent();
        $file->save();

        $node->set('n_news_image', [
          'target_id' => $file->id(),
          'alt' => $alt,
        ]);

        // Generate images styles for the image.
        // Get the responsive image style for the default display.
        $display_settings = $this->entityTypeManager
          ->getStorage('entity_view_display')
          ->load('node.news.default')
          ->getRenderer('n_news_image')
          ->getSettings();
        $responsive_image_style = ResponsiveImageStyle::load($display_settings['responsive_image_style']);

        // Loop through image styles used by responsive image style and generate
        // derivative images.
        foreach ($responsive_image_style->getImageStyleIds() as $image_style) {
          $image_style = ImageStyle::load($image_style);
          $destination_uri = $image_style->buildUri($file->uri->value);
          $image_style->createDerivative($local_image_uri, $destination_uri);
        }
      }
    }
    $node->save();

    // Remove item from state queued-items list.
    $queued_items = $this->state->get('unl_news.nebraska_today.queued_items', []);
    unset($queued_items[$item->id]);
    $queued_items = $this->state->set('unl_news.nebraska_today.queued_items', $queued_items);
  }

}
