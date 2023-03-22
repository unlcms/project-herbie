<?php

namespace Drupal\feeds_test_files\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates CSV source files.
 */
class CsvController extends ControllerBase {

  /**
   * Date format not defined in PHP 5.
   *
   * Example: Sun, 06 Nov 1994 08:49:37 GMT.
   *
   * @var string
   */
  const DATE_RFC7231 = 'D, d M Y H:i:s \G\M\T';

  /**
   * The state handler service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module extension list service.
   *
   * @var Drupal\Core\Extension\ModuleExtensionList|null
   */
  protected $extensionList;

  /**
   * Constructs a CsvController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state handler service.
   * @param Drupal\Core\Extension\ModuleExtensionList $extensionList
   *   The module extension list service.
   */
  public function __construct(StateInterface $state, ModuleExtensionList $extensionList = NULL) {
    $this->state = $state;
    $this->extensionList = $extensionList;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Generates an absolute url to the resources folder.
   *
   * @return string
   *   An absolute url to the resources folder, for example:
   *   http://www.example.com/modules/contrib/feeds/tests/resources
   */
  protected function getResourcesUrl() {
    $resources_path = $this->getModulePath('feeds') . '/tests/resources';

    return Url::fromUri('internal:/' . $resources_path)
      ->setAbsolute()
      ->toString();
  }

  /**
   * Outputs a CSV file pointing to files.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A HTTP response.
   */
  public function files() {
    $assets_url = $this->getResourcesUrl() . '/assets';

    $csv_lines = [
      ['title', 'timestamp', 'file'],
      ['Tubing is awesome', '205200720', $assets_url . '/tubing.jpeg'],
      ['Jeff vs Tom', '428112720', $assets_url . '/foosball.jpeg?10000'],
      ['Attersee', '1151766000', $assets_url . '/attersee.jpeg'],
      ['H Street NE', '1256326995', $assets_url . '/hstreet.jpeg'],
      ['La Fayette Park', '1256326995', $assets_url . '/la fayette.jpeg'],
      ['Attersee 2', '1151766000', $assets_url . '/attersee.JPG'],
    ];

    $csv = '';
    foreach ($csv_lines as $line) {
      $csv .= implode(',', $line) . "\n";
    }

    $response = new Response();
    $response->setContent($csv);
    return $response;
  }

  /**
   * Generates a test feed and simulates last-modified.
   *
   * This is used to test the following:
   * - Ensure that the source is not refetched on a second import when the
   *   source did not change.
   * - Ensure that the source *is* refetched on a second import when the
   *   source *did* change.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A HTTP response.
   */
  public function nodes() {
    $last_modified = $this->state->get('feeds_test_nodes_last_modified');
    if (!$last_modified) {
      $file = 'nodes.csv';
      $last_modified = strtotime('Sun, 19 Nov 1978 05:00:00 GMT');
    }
    else {
      $file = 'nodes_changes2.csv';
    }

    $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : FALSE;
    $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : FALSE;

    $response = new Response();

    // Set header with last modified date.
    $response->headers->set('Last-Modified', gmdate(static::DATE_RFC7231, $last_modified));

    // Return 304 not modified if last modified.
    if ($last_modified == $if_modified_since) {
      $response->headers->set('Status', '304 Not Modified');
      return $response;
    }

    // The following headers force validation of cache:
    $response->headers->set('Expires', $last_modified);
    $response->headers->set('Cache-Control', 'must-revalidate');
    $response->headers->set('Content-Type', 'text/plain; charset=utf-8');

    // Read actual feed from file.
    $csv = file_get_contents($this->getModulePath('feeds') . '/tests/resources/csv/' . $file);

    // And return the file contents.
    $response->setContent($csv);
    return $response;
  }

  /**
   * Gets the path for the specified module.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return string
   *   The Drupal-root relative path to the module directory.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If the module does not exist.
   */
  protected function getModulePath(string $module_name): string {
    return $this->extensionList->getPath($module_name);
  }

}
