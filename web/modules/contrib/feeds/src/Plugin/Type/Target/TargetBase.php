<?php

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginTrait;
use Drupal\feeds\Plugin\Type\PluginBase;

/**
 * A base class for Feed targets.
 *
 * Feed targets are objects where you can map to. Each feed target receives an
 * array of values. A feed target is responsible for converting the values to
 * something usable and then do something with it, usually storing it on a field
 * on the entity.
 *
 * Most feed targets store data on a field. For these target plugins you should
 * usually extend \Drupal\feeds\Plugin\Type\Target\FieldTargetBase instead of
 * extending this class directly. You should extend this class directly if
 * either you find FieldTargetBase not suitable or if you want to do something
 * else than storing data on a field on the entity.
 */
abstract class TargetBase extends PluginBase implements TargetInterface, PluginFormInterface {

  use ConfigurablePluginTrait;

  /**
   * The target definition.
   *
   * @var \Drupal\feeds\TargetDefinitionInterface
   */
  protected $targetDefinition;

  /**
   * Constructs a TargetBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    // Do not call parent, we handle everything ourselves.
    $this->feedType = $configuration['feed_type'];
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->targetDefinition = $configuration['target_definition'];

    unset($configuration['feed_type']);
    unset($configuration['target_definition']);

    // Calling setConfiguration() ensures the configuration is clean and
    // defaults are set.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetDefinition() {
    return $this->targetDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $delta = $form_state->getTriggeringElement()['#delta'];
    $configuration = $form_state->getValue(['mappings', $delta, 'settings']);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return FALSE;
  }

}
