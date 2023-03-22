<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a text field mapper.
 *
 * @FeedsTarget(
 *   id = "text",
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class Text extends StringTarget implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a Text object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $user) {
    $this->user = $user;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');

    if ($field_definition->getType() === 'text_with_summary') {
      $definition->addProperty('summary');
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // At todo. Maybe break these up into separate classes.
    parent::prepareValue($delta, $values);
    $values['format'] = $this->configuration['format'];
  }

  /**
   * Retrieves a list of text formats that the given user may use.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to select formats for.
   *
   * @return \Drupal\filter\FilterFormatInterface[]
   *   An array of text format objects, keyed by the format ID and ordered by
   *   weight.
   */
  protected function getFilterFormats(AccountInterface $account) {
    return filter_formats($account);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + ['format' => 'plain_text'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = [];
    foreach ($this->getFilterFormats($this->user) as $id => $format) {
      $options[$id] = $format->label();
    }
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter format'),
      '#options' => $options,
      '#default_value' => $this->configuration['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    $formats = \Drupal::entityTypeManager()
      ->getStorage('filter_format')
      ->loadByProperties([
        'status' => '1',
        'format' => $this->configuration['format'],
      ]);

    if ($formats) {
      $format = reset($formats);
      $summary[] = $this->t('Format: %format', ['%format' => $format->label()]);
    }
    return $summary;
  }

}
