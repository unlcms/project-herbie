<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Utility\JsonUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for JSON based parsers.
 */
abstract class JsonParserBase extends ParserBase implements ContainerFactoryPluginInterface {

  /**
   * The JSON helper class.
   *
   * @var \Drupal\feeds_ex\Utility\JsonUtility
   */
  protected $utility;

  /**
   * Constructs a JsonParserBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\feeds_ex\Utility\JsonUtility $utility
   *   The JSON helper class.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, JsonUtility $utility) {
    $this->utility = $utility;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('feeds_ex.json_utility')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $this->sources = $feed->getType()->getCustomSources(['json']);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return ['json'];
  }

  /**
   * {@inheritdoc}
   */
  protected function startErrorHandling() {
    // Clear the json errors from previous parsing.
    json_decode('{}');
  }

}
