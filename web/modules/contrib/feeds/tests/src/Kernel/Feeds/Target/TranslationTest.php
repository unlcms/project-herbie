<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\language\ConfigurableLanguageInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Test for the entity field translation.
 *
 * @group feeds
 */
class TranslationTest extends FeedsKernelTestBase {

  use TaxonomyTestTrait;

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * A vocabulary used for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'language',
    'taxonomy',
    'content_translation',
  ];

  /**
   * Feeds translation languages.
   *
   * @var array
   */
  protected $feedsTranslationLanguages = [
    'es',
    'nl',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Add languages.
    foreach ($this->feedsTranslationLanguages as $langcode) {
      $language = $this->container->get('entity_type.manager')->getStorage('configurable_language')->create([
        'id' => $langcode,
      ]);
      $language->save();
    }

    // Set article bundle to be translatable.
    $this->container->get('content_translation.manager')->setEnabled('node', 'article', TRUE);

    // Create a text field.
    $this->createFieldWithStorage('field_alpha');

    // Install taxonomy tables and add a vocabulary.
    $this->vocabulary = $this->installTaxonomyModuleWithVocabulary();
    // And set it as translatable.
    $this->container->get('content_translation.manager')->setEnabled('taxonomy_term', $this->vocabulary->id(), TRUE);

    // Add a term reference field to the article bundle.
    $this->createFieldWithStorage('field_tags', [
      'entity_type' => 'node',
      'bundle' => 'article',
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
              $this->vocabulary->id() => $this->vocabulary->id(),
            ],
          ],
        ],
      ],
    ]);

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title_es' => 'title_es',
      'title_nl' => 'title_nl',
      'body_es' => 'body_es',
      'body_nl' => 'body_nl',
      'terms_es' => 'terms_es',
      'terms_nl' => 'terms_nl',
    ]);
  }

  /**
   * Tests importing content with Spanish translation.
   */
  public function testTranslation() {
    // Add mappings for Spanish.
    $this->addMappings($this->getMappingsInLanguage('es', '_es'));
    $this->feedType->save();

    // Import file that contains both English and Spanish content.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es_nl.csv');
    $this->assertNodeCount(1);

    // Check values on main node.
    $node = Node::load(1);
    $this->assertEquals('HELLO WORLD', $node->title->value);
    $this->assertEmpty($node->field_tags->referencedEntities());

    // Inspect Spanish values.
    $this->assertTrue($node->hasTranslation('es'));
    $translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $translation->title->value);
    $this->assertEquals('Este es el texto del cuerpo.', $translation->field_alpha->value);
    $this->assertEquals($node->uid->value, $translation->uid->value);
    $this->assertNotEmpty($translation->field_tags->referencedEntities());
    $referenced_entities = $translation->field_tags->referencedEntities();
    $first_tag = reset($referenced_entities);
    $this->assertEquals('Termino de taxonomía', $first_tag->name->value);
  }

  /**
   * Tests importing only in a language that is not the default language.
   */
  public function testImportNonDefaultLanguage() {
    // Set language to Spanish.
    $configuration = $this->feedType->getProcessor()->getConfiguration();
    $configuration['langcode'] = 'es';
    $this->feedType->getProcessor()->setConfiguration($configuration);

    // Set mappings for Spanish.
    $this->feedType->setMappings($this->getMappingsInLanguage('es'));
    $this->feedType->save();

    // Import Spanish content.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es.csv');
    $this->assertNodeCount(1);

    // Assert that Spanish values were created.
    $node = Node::load(1);
    $this->assertEquals('es', $node->language()->getId());
    $this->assertEquals('HOLA MUNDO', $node->title->value);
    $this->assertEquals('Este es el texto del cuerpo.', $node->field_alpha->value);
  }

  /**
   * Tests that the language setting on the processor is respected.
   *
   * In this case there's no language configured on the targets.
   */
  public function testImportInProcessorConfiguredLanguage() {
    // Set language to Spanish.
    $configuration = $this->feedType->getProcessor()->getConfiguration();
    $configuration['langcode'] = 'es';
    $this->feedType->getProcessor()->setConfiguration($configuration);

    // Set mappings without configuring language.
    $this->feedType->setMappings($this->getMappingsInLanguage(NULL));
    $this->feedType->save();

    // Import content.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es.csv');
    $this->assertNodeCount(1);

    // Assert that Spanish values were created.
    $node = Node::load(1);
    $this->assertEquals('es', $node->language()->getId());
    $this->assertEquals('HOLA MUNDO', $node->title->value);
    $this->assertEquals('Este es el texto del cuerpo.', $node->field_alpha->value);

    // Assert that the created term is in the Spanish language.
    $term = Term::load(1);
    $this->assertEquals('Termino de taxonomía', $term->name->value);
    $this->assertEquals('es', $term->langcode->value);
  }

  /**
   * Tests importing values for two languages separately.
   *
   * This tests configures the feed type to first import the Spanish values.
   * After importing the Spanish content, the language setting on each target is
   * changed from Spanish to Dutch (for those that had it). It is expected that
   * importing the Dutch content in this case, does not clear out the Spanish
   * values, because there is not being mapped to fields in that language
   * anymore.
   */
  public function testMappingFieldsAnotherLanguageImport() {
    // Set to update existing nodes.
    $configuration = $this->feedType->getProcessor()->getConfiguration();
    $configuration['update_existing'] = ProcessorInterface::UPDATE_EXISTING;
    $this->feedType->getProcessor()->setConfiguration($configuration);

    // Add mappings for Spanish.
    $this->addMappings($this->getMappingsInLanguage('es'));

    // And save the feed type to save all configuration changes.
    $this->feedType->save();

    // Import Spanish content.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es.csv');
    $this->assertNodeCount(1);

    // Assert that Spanish values were created.
    $node = Node::load(1);
    $this->assertTrue($node->hasTranslation('es'));
    $spanish_translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $spanish_translation->title->value);
    $this->assertEquals('Este es el texto del cuerpo.', $spanish_translation->field_alpha->value);

    // Change the feed type to import Dutch values instead.
    $mappings = $this->feedType->getMappings();
    foreach ($mappings as $delta => &$mapping) {
      if (isset($mapping['settings']['language']) && $mapping['settings']['language'] == 'es') {
        $mapping['settings']['language'] = 'nl';

        // Change configuration on the target plugin as well.
        $this->feedType->getTargetPlugin($delta)->setConfiguration($mapping['settings']);
      }
    }
    $this->feedType->setMappings($mappings);
    $this->feedType->save();

    // Import Dutch content.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_nl.csv');
    $this->assertNodeCount(1);

    // Reload node and check Dutch values.
    $node = $this->reloadEntity($node);
    $this->assertTrue($node->hasTranslation('nl'));
    $dutch_translation = $node->getTranslation('nl');
    $this->assertEquals('HALLO WERELD', $dutch_translation->title->value);
    $this->assertEquals('Dit is de bodytekst.', $dutch_translation->field_alpha->value);

    // Ensure that the Spanish translation still exists.
    $this->assertTrue($node->hasTranslation('es'));
    $spanish_translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $spanish_translation->title->value);
    $this->assertEquals('Este es el texto del cuerpo.', $spanish_translation->field_alpha->value);
  }

  /**
   * Tests importing values for multiple languages at once.
   *
   * On the feed type, mappings are created for two languages: Spanish and
   * Dutch. A file gets imported that has values for both languages. It is
   * expected that for both these language values get imported.
   */
  public function testValuesForMultipleLanguagesAreImported() {
    // Add mappings for Spanish and Dutch.
    $this->addMappings($this->getMappingsInLanguage('es', '_es'));
    $this->addMappings($this->getMappingsInLanguage('nl', '_nl'));
    $this->feedType->save();

    // Import file that has items with both Spanish and Dutch field values.
    $feed = $this->importContent($this->resourcesPath() . '/csv/translation/content_es_nl.csv');
    $this->assertNodeCount(1);

    $node = Node::load(1);
    $this->assertEquals('HELLO WORLD', $node->title->value);

    // Inspect Spanish values.
    $this->assertTrue($node->hasTranslation('es'));
    $spanish_translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $spanish_translation->title->value);
    $this->assertEquals('Este es el texto del cuerpo.', $spanish_translation->field_alpha->value);
    $this->assertEquals($node->uid->value, $spanish_translation->uid->value);
    $this->assertNotEmpty($spanish_translation->field_tags->referencedEntities());
    $spanish_referenced_entities = $spanish_translation->field_tags->referencedEntities();
    $spanish_translation_first_tag = reset($spanish_referenced_entities);
    $this->assertEquals('Termino de taxonomía', $spanish_translation_first_tag->name->value);

    // Inspect Dutch values.
    $this->assertTrue($node->hasTranslation('nl'));
    $dutch_translation = $node->getTranslation('nl');
    $this->assertEquals('HALLO WERELD', $dutch_translation->title->value);
    $this->assertEquals('Dit is de bodytekst.', $dutch_translation->field_alpha->value);
    $this->assertNotEmpty($dutch_translation->field_tags->referencedEntities());
    $dutch_referenced_entities = $dutch_translation->field_tags->referencedEntities();
    $dutch_translation_first_tag = reset($dutch_referenced_entities);
    $this->assertEquals('Taxonomieterm', $dutch_translation_first_tag->name->value);
  }

  /**
   * Tests if values are kept being imported after removing a language.
   *
   * @todo In the D7 version, the values were getting imported as language
   * neutral instead. Should we preserve that behavior?
   */
  public function testValuesAreImportedAfterRemovingLanguage() {
    // Add mappings for Spanish.
    $this->addMappings($this->getMappingsInLanguage('es'));
    $this->feedType->save();

    // Now remove the Spanish language.
    $spanish_language = $this->container->get('entity_type.manager')->getStorage('configurable_language')->loadByProperties(['id' => 'es']);
    if (!empty($spanish_language['es']) && $spanish_language['es'] instanceof ConfigurableLanguageInterface) {
      $spanish_language['es']->delete();
    }

    // Try to import Spanish values.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es.csv');
    $this->assertNodeCount(1);

    // Check the imported values.
    $node = Node::load(1);
    $this->assertEquals('HOLA MUNDO', $node->title->value);
    $this->assertFalse($node->hasTranslation('es'));
  }

  /**
   * Tests importing a translation for an existing node.
   *
   * This test creates a node with a Dutch translation. It then imports a
   * translation for an other language: Spanish. It is then expected that the
   * Dutch translation is kept.
   */
  public function testImportTranslationForExistingNode() {
    // Create a node with Dutch values. Set a value for feed item's
    // guid, even though the node was not originally imported with Feeds.
    Node::create([
      'type' => 'article',
      'title' => 'HALLO WERELD',
      'field_alpha' => 'Dit is de bodytekst.',
      'langcode' => 'nl',
      'feeds_item' => [
        'guid' => 1,
        'target_id' => 1,
      ],
    ])->save();

    // Set to update existing nodes.
    $configuration = $this->feedType->getProcessor()->getConfiguration();
    $configuration['update_existing'] = ProcessorInterface::UPDATE_EXISTING;
    $this->feedType->getProcessor()->setConfiguration($configuration);

    // Remove mapping to 'normal' title.
    $this->feedType->removeMapping(1);
    // And add Spanish mappings.
    $this->addMappings($this->getMappingsInLanguage('es'));
    $this->feedType->save();

    // Update this item with Feeds.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es.csv');
    $this->assertNodeCount(1);

    // Assert that a Spanish translation was created.
    $node = Node::load(1);
    $this->assertTrue($node->hasTranslation('es'));
    $spanish_translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $spanish_translation->title->value);
    $this->assertEquals('Este es el texto del cuerpo.', $spanish_translation->field_alpha->value);
    $this->assertEquals($node->uid->value, $spanish_translation->uid->value);
    $this->assertNotEmpty($spanish_translation->field_tags->referencedEntities());
    $spanish_referenced_entities = $spanish_translation->field_tags->referencedEntities();
    $spanish_translation_first_tag = reset($spanish_referenced_entities);
    $this->assertEquals('Termino de taxonomía', $spanish_translation_first_tag->name->value);

    // Assert that Dutch values still exist.
    $this->assertTrue($node->hasTranslation('nl'));
    $dutch_translation = $node->getTranslation('nl');
    $this->assertEquals('HALLO WERELD', $dutch_translation->title->value);
    $this->assertEquals('Dit is de bodytekst.', $dutch_translation->field_alpha->value);
  }

  /**
   * Tests if auto-created terms are imported in the configured language.
   *
   * This test uses a vocabulary that is configured to be multilingual. On the
   * feed type, there is being mapped to a taxonomy reference target. This
   * target is configured as follows:
   * - Language is set to Spanish;
   * - Auto-create option is enabled.
   *
   * A Spanish value is imported into the taxonomy reference field that does not
   * exist in the vocabulary yet. It is expected that a term gets created in the
   * vocabulary and that it gets the Spanish language assigned.
   */
  public function testAutocreatedTermLanguage() {
    // Make the vocabulary to test with multilingual.
    $this->container->get('content_translation.manager')->setEnabled('taxonomy_term', $this->vocabulary->id(), TRUE);

    // Add mappings for Spanish.
    $this->addMappings($this->getMappingsInLanguage('es'));
    $this->feedType->save();

    // Import Spanish content.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es.csv');
    $this->assertNodeCount(1);

    // Assert that the created term is in the Spanish language.
    $term = Term::load(1);
    $this->assertEquals('Termino de taxonomía', $term->name->value);
    $this->assertEquals('es', $term->langcode->value);
  }

  /**
   * Tests importing auto-created terms when no language is configured for it.
   */
  public function testAutocreatedTermDefaultLanguage() {
    $this->feedType->addMapping([
      'target' => 'field_tags',
      'map' => ['target_id' => 'terms'],
      'settings' => [
        'reference_by' => 'name',
        'language' => NULL,
        'autocreate' => 1,
      ],
    ]);
    $this->feedType->save();

    // Import Spanish content.
    $this->importContent($this->resourcesPath() . '/csv/translation/content_es.csv');
    $this->assertNodeCount(1);

    // Assert that the term was created in the default language.
    $default_langcode = $this->container->get('language.default')->get()->getId();
    $term = Term::load(1);
    $this->assertEquals('Termino de taxonomía', $term->name->value);
    $this->assertEquals($default_langcode, $term->langcode->value);
  }

  /**
   * Tests if values are cleared out when importing empty values.
   *
   * When importing empty values for a specific language, it is expected that
   * the values for that translation are getting emptied.
   */
  public function testClearOutValues() {
    // Set to update existing nodes.
    $configuration = $this->feedType->getProcessor()->getConfiguration();
    $configuration['update_existing'] = ProcessorInterface::UPDATE_EXISTING;
    $this->feedType->getProcessor()->setConfiguration($configuration);

    // Add mappings for Spanish and Dutch.
    $this->addMappings($this->getMappingsInLanguage('es', '_es'));
    $this->addMappings($this->getMappingsInLanguage('nl', '_nl'));

    // And save the feed type to save all configuration changes.
    $this->feedType->save();

    // Import file that has items with both Spanish and Dutch field values.
    $feed = $this->importContent($this->resourcesPath() . '/csv/translation/content_es_nl.csv');
    $this->assertNodeCount(1);

    // Assert that node 1 has translations for both languages.
    $node = Node::load(1);
    $this->assertTrue($node->hasTranslation('es'));
    $this->assertTrue($node->hasTranslation('nl'));

    // Now import a file where the Dutch remained, but the Spanish values were
    // removed.
    $feed->setSource($this->resourcesPath() . '/csv/translation/content_es_nl_empty.csv');
    $feed->save();
    $feed->import();

    // Check that the Spanish values are gone, but the Dutch values are still
    // there.
    $node = $this->reloadEntity($node);
    $spanish = $node->getTranslation('es');
    $dutch = $node->getTranslation('nl');

    $fields = [
      'field_alpha' => 'value',
      'field_tags' => 'target_id',
    ];
    foreach ($fields as $field_name => $property) {
      $this->assertEmpty($spanish->{$field_name}->{$property});
    }

    // Inspect availability of Dutch values.
    $this->assertEquals('HALLO WERELD', $dutch->title->value);
    $this->assertEquals('Dit is de bodytekst.', $dutch->field_alpha->value);
    $referenced_entities = $dutch->field_tags->referencedEntities();
    $first_tag = reset($referenced_entities);
    $this->assertEquals('Taxonomieterm', $first_tag->name->value);
  }

  /**
   * Overrides FeedCreationTrait::getDefaultMappings().
   */
  protected function getDefaultMappings() {
    return [
      [
        'target' => 'feeds_item',
        'map' => ['guid' => 'guid'],
        'unique' => ['guid' => TRUE],
        'settings' => [],
      ],
      [
        'target' => 'title',
        'map' => ['value' => 'title'],
        'settings' => [
          'language' => NULL,
        ],
        'unique' => [
          'value' => 1,
        ],
      ],
    ];
  }

  /**
   * Creates mappings for each field in a specified language.
   *
   * @param string $langcode
   *   The code of the desired language.
   * @param string $source_suffix
   *   (optional) The suffix to add to the mapping sources.
   *
   * @return array
   *   A list of mappings.
   */
  protected function getMappingsInLanguage($langcode, $source_suffix = '') {
    return [
      [
        'target' => 'title',
        'map' => ['value' => 'title' . $source_suffix],
        'settings' => [
          'language' => $langcode,
        ],
      ],
      [
        'target' => 'field_alpha',
        'map' => ['value' => 'body' . $source_suffix],
        'settings' => [
          'language' => $langcode,
        ],
      ],
      [
        'target' => 'field_tags',
        'map' => ['target_id' => 'terms' . $source_suffix],
        'settings' => [
          'reference_by' => 'name',
          'language' => $langcode,
          'autocreate' => 1,
        ],
      ],
    ];
  }

  /**
   * Creates a feed and imports the given source.
   *
   * @param string $source
   *   The absolute path to the source.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The created feed.
   */
  protected function importContent($source) {
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $source,
    ]);
    $feed->import();

    return $feed;
  }

  /**
   * Adds multiple mappings to the feed type.
   *
   * @param array $mappings
   *   A list of mappings.
   */
  public function addMappings(array $mappings) {
    foreach ($mappings as $mapping_field) {
      $this->feedType->addMapping($mapping_field);
    }
  }

}
