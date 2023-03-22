<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Utility\Token;
use Drupal\feeds\EntityFinderInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedTypeInterface;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\feeds\Traits\FeedsMockingTrait;
use Drupal\user\Entity\Role;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Base class for file field tests.
 */
abstract class FileTestBase extends FeedsKernelTestBase {

  use ProphecyTrait;
  use FeedsMockingTrait;

  /**
   * The entity type manager prophecy used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The http client prophecy used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Token service.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The file and stream wrapper helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The FeedsTarget plugin being tested.
   *
   * @var \Drupal\feeds\Feeds\Target\File
   */
  protected $targetPlugin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpFileFields();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->client = $this->prophesize(ClientInterface::class);
    $this->token = $this->prophesize(Token::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->entityFieldManager->getFieldStorageDefinitions('file')->willReturn([]);
    $this->entityFinder = $this->prophesize(EntityFinderInterface::class);
    $this->entityFinder->findEntities(Argument::cetera())->willReturn([]);
    $this->fileSystem = $this->prophesize(FileSystemInterface::class);

    // Made-up entity type that we are referencing to.
    $referenceable_entity_type = $this->prophesize(EntityTypeInterface::class);
    $referenceable_entity_type->getKey('label')->willReturn('file label');
    $this->entityTypeManager->getDefinition('file')->willReturn($referenceable_entity_type)->shouldBeCalled();

    $configuration = [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $this->getTargetDefinition(),
    ];

    $this->targetPlugin = $this->getMockBuilder($this->getTargetPluginClass())
      ->setMethods(['getDestinationDirectory'])
      ->setConstructorArgs([
        $configuration,
        'file',
        [],
        $this->entityTypeManager->reveal(),
        $this->client->reveal(),
        $this->token->reveal(),
        $this->entityFieldManager->reveal(),
        $this->entityFinder->reveal(),
        $this->fileSystem->reveal(),
      ])
      ->getMock();

    $this->targetPlugin->expects($this->any())
      ->method('getDestinationDirectory')
      ->will($this->returnValue('public:/'));

    // Role::load fails without installing the user config.
    $this->installConfig(['user']);
    // Give anonymous users permission to access content, so that they can view
    // and download public files. Without this we get an access denied error
    // when trying to import public files.
    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('access content')
      ->save();
  }

  /**
   * Returns the file target class to instantiate.
   *
   * @return string
   *   The class for the file target plugin.
   */
  abstract protected function getTargetPluginClass();

  /**
   * Returns target definition to pass to the target constructor.
   *
   * @return array
   *   The target definition for the file target.
   */
  abstract protected function getTargetDefinition();

  /**
   * @covers ::prepareValue
   * @dataProvider dataProviderPrepareValue
   *
   * @param array $expected
   *   The expected values.
   * @param array $values
   *   The values to pass to prepareValue().
   * @param string $expected_exception
   *   (optional) The name of the expected exception class.
   * @param string $expected_exception_message
   *   (optional) The expected exception message.
   */
  public function testPrepareValue(array $expected, array $values, $expected_exception = NULL, $expected_exception_message = NULL) {
    $method = $this->getProtectedClosure($this->targetPlugin, 'prepareValue');

    // Add in base URL.
    if (isset($values['target_id'])) {
      $file_path = strtr($values['target_id'], [
        '[url]' => $this->resourcesPath(),
      ]);
      $values['target_id'] = strtr($values['target_id'], [
        '[url]' => $this->resourcesUrl(),
      ]);

      // Set guzzle client response.
      if (file_exists($file_path)) {
        $this->client->request('GET', $values['target_id'], Argument::any())->will(function () use ($file_path) {
          return new Response(200, [], file_get_contents($file_path));
        });
      }
      else {
        $this->client->request('GET', $values['target_id'], Argument::any())->will(function () {
          return new Response(404, [], '');
        });
      }
    }

    // Set expected exception if there is one expected.
    if ($expected_exception) {
      $this->expectException($expected_exception);
      if ($expected_exception_message) {
        $expected_exception_message = strtr($expected_exception_message, [
          '[url]' => $this->resourcesUrl(),
        ]);
        $this->expectExceptionMessage($expected_exception_message);
      }
    }

    // Call prepareValue().
    $method(0, $values);

    // Asserts.
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $values[$key]);
    }
  }

  /**
   * Data provider for testPrepareValue().
   */
  public function dataProviderPrepareValue() {
    $return = [
      // Empty file target value.
      'empty-file' => [
        'expected' => [],
        'values' => [
          'target_id' => '',
        ],
        'expected_exception' => EmptyFeedException::class,
        'expected_exception_message' => 'The given file url is empty.',
      ],
      // Importing a file url that exists.
      'file-success' => [
        'expected' => [
          'target_id' => 1,
        ],
        'values' => [
          'target_id' => '[url]/assets/attersee.jpeg',
        ],
      ],
      // Importing a file with uppercase extension.
      'file-uppercase' => [
        'expected' => [
          'target_id' => 1,
        ],
        'values' => [
          'target_id' => '[url]/assets/attersee.JPG',
        ],
      ],

      // Importing a file url that does *not* exist.
      'file-not-found' => [
        'expected' => [],
        'values' => [
          'target_id' => '[url]/assets/not-found.jpg',
        ],
        'expected_exception' => TargetValidationException::class,
        'expected_exception_message' => 'Download of <em class="placeholder">[url]/assets/not-found.jpg</em> failed with code 404.',
      ],

      // Importing a file with invalid extension.
      'invalid-extension' => [
        'expected' => [],
        'values' => [
          'target_id' => '[url]/file.foo',
        ],
        'expected_exception' => TargetValidationException::class,
        'expected_exception_message' => 'The file, <em class="placeholder">[url]/file.foo</em>, failed to save because the extension, <em class="placeholder">foo</em>, is invalid.',
      ],
    ];
    return $return;
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
    catch (FileException | EntityStorageException $e) {
      return FALSE;
    }
  }

}
