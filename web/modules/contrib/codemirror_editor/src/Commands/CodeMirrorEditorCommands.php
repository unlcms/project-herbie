<?php

namespace Drupal\codemirror_editor\Commands;

use Drupal\codemirror_editor\LibraryBuilderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drush integration for CodeMirror editor module.
 */
class CodeMirrorEditorCommands extends DrushCommands {

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * JS collection optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsCollectionOptimizer;

  /**
   * CSS collection optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   */
  public function __construct(
    LibraryDiscoveryInterface $library_discovery,
    Client $http_client,
    AssetCollectionOptimizerInterface $js_collection_optimizer,
    AssetCollectionOptimizerInterface $css_collection_optimizer,
    StateInterface $state,
    TimeInterface $time,
    FileSystemInterface $file_system
  ) {
    parent::__construct();
    $this->libraryDiscovery = $library_discovery;
    $this->httpClient = $http_client;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->state = $state;
    $this->time = $time;
    $this->fileSystem = $file_system;
  }

  /**
   * Downloads CodeMirror library.
   *
   * @command codemirror:download
   * @aliases codemirror-download
   */
  public function downloadCodemirror() {
    $io = $this->io();

    $cm_library = $this->libraryDiscovery->getLibraryByName('codemirror_editor', 'codemirror');

    $source_base_path = str_replace('{version}', LibraryBuilderInterface::CODEMIRROR_VERSION, LibraryBuilderInterface::CDN_URL);

    $destination_base_path = DRUPAL_ROOT . LibraryBuilderInterface::LIBRARY_PATH;

    $assets = array_merge($cm_library['js'], $cm_library['css']);
    foreach ($assets as $asset) {

      if (empty($asset['type']) || $asset['type'] != 'external') {
        $source = $source_base_path . '/' . explode(ltrim(LibraryBuilderInterface::LIBRARY_PATH, '/'), $asset['data'])[1];
        $destination = DRUPAL_ROOT . '/' . $asset['data'];
      }
      else {
        $source = $asset['data'];
        $destination = $destination_base_path . explode($source_base_path, $asset['data'])[1];
      }

      $io->write("<info>$source</info>");

      $directory = dirname($destination);
      if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
        $io->error("Could not create directory $directory.");
        return 1;
      }

      try {
        $response = $this->httpClient->get($source, ['sink' => $destination]);
      }
      catch (\Exception $exception) {
        $io->error($exception->getMessage());
        return 1;
      }

      $status_code = $response->getStatusCode();
      Response::$statusTexts[$status_code];
      $io->writeln(' [' . $status_code . ' ' . Response::$statusTexts[$status_code] . ']');
    }

    // Clear asset caches.
    $this->jsCollectionOptimizer->deleteAll();
    $this->cssCollectionOptimizer->deleteAll();
    $this->state->set('system.css_js_query_string', base_convert($this->time->getRequestTime(), 10, 36));

    $io->success("CodeMirror library has been downloaded into $destination_base_path directory.");
  }

}
