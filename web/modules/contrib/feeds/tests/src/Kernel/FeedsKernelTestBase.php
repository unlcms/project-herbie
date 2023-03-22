<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;
use Drupal\Tests\feeds\Traits\FeedsReflectionTrait;

/**
 * Provides a base class for Feeds kernel tests.
 */
abstract class FeedsKernelTestBase extends EntityKernelTestBase {

  use FeedCreationTrait;
  use FeedsCommonTrait;
  use FeedsReflectionTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install database schemes and config.
    $this->installEntitySchema('feeds_feed');
    $this->installEntitySchema('feeds_subscription');
    $this->installSchema('feeds', 'feeds_clean_list');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['feeds']);

    // Create a content type.
    $this->setUpNodeType();
  }

  /**
   * Installs the taxonomy module and adds a vocabulary.
   *
   * @return \Drupal\taxonomy\VocabularyInterface
   *   The created vocabulary.
   */
  protected function installTaxonomyModuleWithVocabulary() {
    // Install taxonomy module and schema.
    $this->installModule('taxonomy');
    $this->installConfig(['filter', 'taxonomy']);
    $this->installEntitySchema('taxonomy_term');

    // Create tags vocabulary.
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->create([
      'vid' => 'tags',
      'name' => 'Tags',
    ]);
    $vocabulary->save();

    return $vocabulary;
  }

  /**
   * Installs body field (not needed for every kernel test).
   */
  protected function setUpBodyField() {
    $this->installConfig(['field', 'filter', 'node']);
    node_add_body_field($this->nodeType);
  }

  /**
   * Installs a taxonomy term reference field.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary
   *   The created vocabulary.
   */
  protected function setUpTermReferenceField() {
    $vocabulary = $this->installTaxonomyModuleWithVocabulary();

    // Create field for article content type.
    $this->createFieldWithStorage('field_tags', [
      'type' => 'entity_reference',
      'storage' => [
        'settings' => [
          'target_type' => 'taxonomy_term',
        ],
      ],
      'field' => [
        'settings' => [
          'handler' => 'default',
          'handler_settings' => [
            // Restrict selection of terms to a single vocabulary.
            'target_bundles' => [
              $vocabulary->id() => $vocabulary->id(),
            ],
          ],
        ],
      ],
    ]);

    return $vocabulary;
  }

  /**
   * Installs a file and image fields (not needed for every kernel test).
   */
  protected function setUpFileFields() {
    // Create a file field.
    $this->installModule('file');
    $this->installConfig(['field', 'node', 'file']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->createFieldWithStorage('field_file', [
      'type' => 'file',
      'bundle' => 'article',
      'field' => [
        'settings' => ['file_extensions' => 'txt'],
      ],
    ]);

    // Create an image field.
    $this->installModule('image');
    $this->installConfig(['image']);

    $this->createFieldWithStorage('field_image', [
      'type' => 'image',
      'bundle' => 'article',
      'field' => [
        'settings' => ['file_extensions' => 'svg'],
      ],
    ]);
  }

}
