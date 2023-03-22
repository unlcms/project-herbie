<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\feeds\EntityFinderInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FieldTargetDefinition;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a file field mapper.
 *
 * @FeedsTarget(
 *   id = "file",
 *   field_types = {"file"}
 * )
 */
class File extends EntityReference {

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The list of allowed file extensions.
   *
   * @var string[]
   */
  protected $fileExtensions;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The file and stream wrapper helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a File object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\feeds\EntityFinderInterface $entity_finder
   *   The Feeds entity finder service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file and stream wrapper helper.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ClientInterface $client, Token $token, EntityFieldManagerInterface $entity_field_manager, EntityFinderInterface $entity_finder, FileSystemInterface $file_system) {
    $this->client = $client;
    $this->token = $token;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $entity_finder);
    $this->fileExtensions = array_filter(explode(' ', $this->settings['file_extensions']));
    $this->fileSystem = $file_system;
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
      $container->get('http_client'),
      $container->get('token'),
      $container->get('entity_field.manager'),
      $container->get('feeds.entity_finder'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id')
      ->addProperty('description');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    foreach ($values as $column => $value) {
      switch ($column) {
        case 'description':
          $values[$column] = (string) $value;
          break;

        case 'target_id':
          $values[$column] = $this->getFile($value);
          break;
      }
    }

    $values['display'] = (int) $this->settings['display_default'];
  }

  /**
   * {@inheritdoc}
   *
   * Filesize and MIME-type aren't sensible fields to match on so these are
   * filtered out.
   */
  protected function filterFieldTypes(FieldStorageDefinitionInterface $field) {
    $ignore_fields = [
      'filesize',
      'filemime',
    ];

    return in_array($field->getName(), $ignore_fields) ? FALSE : parent::filterFieldTypes($field);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType() {
    return 'file';
  }

  /**
   * {@inheritdoc}
   *
   * The file entity doesn't support any bundles. Providing an empty array here
   * will prevent the bundle check from being added in the find entity query.
   */
  protected function getBundles() {
    return [];
  }

  /**
   * Returns a file id given a url.
   *
   * @param string $value
   *   A URL file object.
   *
   * @return int
   *   The file id.
   *
   * @throws \Drupal\feeds\Exception\EmptyFeedException
   *   In case an empty file url is given.
   */
  protected function getFile($value) {
    if (empty($value)) {
      // No file.
      throw new EmptyFeedException('The given file url is empty.');
    }

    // Perform a lookup agains the value using the configured reference method.
    if (FALSE !== ($fid = $this->findEntity($this->configuration['reference_by'], $value))) {
      return $fid;
    }

    // Compose file path.
    $filepath = $this->getDestinationDirectory() . '/' . $this->getFileName($value);

    switch ($this->configuration['existing']) {
      case FileSystemInterface::EXISTS_ERROR:
        if (file_exists($filepath) && $fid = $this->findEntity('uri', $filepath)) {
          return $fid;
        }
        if ($file = $this->writeData($this->getContent($value), $filepath, FileSystemInterface::EXISTS_REPLACE)) {
          return $file->id();
        }
        break;

      default:
        if ($file = $this->writeData($this->getContent($value), $filepath, $this->configuration['existing'])) {
          return $file->id();
        }
    }

    // Something bad happened while trying to save the file to the database. We
    // need to throw an exception so that we don't save an incomplete field
    // value.
    throw new TargetValidationException($this->t('There was an error saving the file: %file', [
      '%file' => $filepath,
    ]));
  }

  /**
   * Prepares destination directory and returns its path.
   *
   * @return string
   *   The directory to save the file to.
   */
  protected function getDestinationDirectory() {
    $destination = $this->token->replace($this->settings['uri_scheme'] . '://' . trim($this->settings['file_directory'], '/'));
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::CREATE_DIRECTORY);
    return $destination;
  }

  /**
   * Extracts the file name from the given url and checks for valid extension.
   *
   * @param string $url
   *   The URL to get the file name for.
   *
   * @return string
   *   The file name.
   *
   * @throws \Drupal\feeds\Exception\TargetValidationException
   *   In case the file extension is not valid.
   */
  protected function getFileName($url) {
    $filename = trim(\Drupal::service('file_system')->basename($url), " \t\n\r\0\x0B.");
    // Remove query string from file name, if it has one.
    list($filename) = explode('?', $filename);
    $extension = substr($filename, strrpos($filename, '.') + 1);

    if (!preg_grep('/' . $extension . '/i', $this->fileExtensions)) {
      throw new TargetValidationException($this->t('The file, %url, failed to save because the extension, %ext, is invalid.', [
        '%url' => $url,
        '%ext' => $extension,
      ]));
    }

    return $filename;
  }

  /**
   * Attempts to download the file at the given url.
   *
   * @param string $url
   *   The URL to download a file from.
   *
   * @return string
   *   The file contents.
   *
   * @throws \Drupal\feeds\Exception\TargetValidationException
   *   In case the file could not be downloaded.
   */
  protected function getContent($url) {
    $response = $this->client->request('GET', $url);

    if ($response->getStatusCode() >= 400) {
      $args = [
        '%url' => $url,
        '@code' => $response->getStatusCode(),
      ];
      throw new TargetValidationException($this->t('Download of %url failed with code @code.', $args));
    }

    return (string) $response->getBody();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['existing' => FileSystemInterface::EXISTS_ERROR] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Inject $user.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = [
      FileSystemInterface::EXISTS_REPLACE => $this->t('Replace'),
      FileSystemInterface::EXISTS_RENAME => $this->t('Rename'),
      FileSystemInterface::EXISTS_ERROR => $this->t('Ignore'),
    ];

    $form['existing'] = [
      '#type' => 'select',
      '#title' => $this->t('Handle existing files'),
      '#options' => $options,
      '#default_value' => $this->configuration['existing'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    switch ($this->configuration['existing']) {
      case FileSystemInterface::EXISTS_REPLACE:
        $message = 'Replace';
        break;

      case FileSystemInterface::EXISTS_RENAME:
        $message = 'Rename';
        break;

      case FileSystemInterface::EXISTS_ERROR:
        $message = 'Ignore';
        break;
    }

    $summary[] = $this->t('Existing files: %existing', ['%existing' => $message]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($value) {
    if (!strlen(trim($value))) {
      return FALSE;
    }

    $bundles = $this->getBundles();

    $entity = $this->entityTypeManager->getStorage($this->getEntityType())->create([
      $this->getLabelKey() => $value,
      $this->getBundleKey() => reset($bundles),
      'uri' => $value,
    ]);
    $entity->save();

    return $entity->id();
  }

  /**
   * Saves a file to the specified destination and creates a database entry.
   *
   * @param string $data
   *   A string containing the contents of the file.
   * @param string|null $destination
   *   (optional) A string containing the destination URI. This must be a stream
   *   wrapper URI. If no value or NULL is provided, a randomized name will be
   *   generated and the file will be saved using Drupal's default files scheme,
   *   usually "public://".
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_ERROR: Do nothing and return FALSE.
   *
   * @return \Drupal\file\FileInterface|false
   *   A file entity, or FALSE on error.
   */
  protected function writeData($data, $destination = NULL, $replace = FileSystemInterface::EXISTS_RENAME) {
    if (empty($destination)) {
      $destination = \Drupal::config('system.file')->get('default_scheme') . '://';
    }
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $fileRepository = \Drupal::service('file.repository');
    try {
      return $fileRepository->writeData($data, $destination, $replace);
    }
    catch (EntityStorageException | FileException $e) {
      return FALSE;
    }
  }

}
