<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a password field mapper.
 *
 * @FeedsTarget(
 *   id = "password",
 *   field_types = {"password"}
 * )
 */
class Password extends FieldTargetBase implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * Unencrypted password.
   */
  const PASS_UNENCRYPTED = 'plain';

  /**
   * MD5 encrypted password.
   */
  const PASS_MD5 = 'md5';

  /**
   * SHA512 encrypted password.
   */
  const PASS_SHA512 = 'sha512';

  /**
   * The password hash service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordHasher;

  /**
   * Constructs a new Password object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Password\PasswordInterface $password_hasher
   *   The password hash service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PasswordInterface $password_hasher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->passwordHasher = $password_hasher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('password')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->setDescription('Password of this user.');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // If the value isn't set or isn't a string, we can't work with it.
    if (!isset($values['value']) || !is_string($values['value'])) {
      return;
    }

    $values['value'] = trim($values['value']);
    switch ($this->configuration['pass_encryption']) {
      case static::PASS_UNENCRYPTED:
        $values['pre_hashed'] = FALSE;
        break;

      case static::PASS_MD5:
        $new_hash = $this->passwordHasher->hash($values['value']);
        if (!$new_hash) {
          throw new TargetValidationException($this->t('Failed to hash the password.'));
        }
        // Indicate an updated password.
        $values['value'] = 'U' . $new_hash;
        $values['pre_hashed'] = TRUE;
        break;

      case static::PASS_SHA512:
        $values['pre_hashed'] = TRUE;
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'pass_encryption' => static::PASS_UNENCRYPTED,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['pass_encryption'] = [
      '#type' => 'select',
      '#title' => $this->t('Password encryption'),
      '#options' => $this->encryptionOptions(),
      '#default_value' => $this->configuration['pass_encryption'],
    ];

    return $form;
  }

  /**
   * Returns the list of available password encryption methods.
   *
   * @return array
   *   An array of password encryption option titles.
   *
   * @see passFormCallback()
   */
  protected function encryptionOptions() {
    return [
      self::PASS_UNENCRYPTED => $this->t('Unencrypted'),
      self::PASS_MD5 => $this->t('MD5 (used in older versions of Drupal)'),
      self::PASS_SHA512 => $this->t('Hashed'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    switch ($this->configuration['pass_encryption']) {
      case static::PASS_UNENCRYPTED:
        $summary[] = $this->t('Passwords are in plain text format.');
        break;

      case static::PASS_MD5:
        $summary[] = $this->t('Passwords are in MD5 format.');
        break;

      case static::PASS_SHA512:
        $summary[] = $this->t('Passwords are pre-hashed.');
        break;
    }

    return $summary;
  }

}
