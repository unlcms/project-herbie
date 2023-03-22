<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Exception\ReferenceNotFoundException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use Drupal\user\RoleInterface;

/**
 * Defines a user role mapper.
 *
 * @FeedsTarget(
 *   id = "user_role",
 * )
 */
class UserRole extends ConfigEntityReference {

  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition) {
    $processor = $feed_type->getProcessor();

    if (!$processor instanceof EntityProcessorInterface) {
      return $targets;
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($processor->entityType(), $processor->bundle());

    foreach ($field_definitions as $id => $field_definition) {
      if ($field_definition->getType() == 'entity_reference' && $field_definition->getSetting('target_type') == 'user_role') {
        if ($target = static::prepareTarget($field_definition)) {
          $target->setPluginId($definition['id']);
          $targets[$id] = $target;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values) {
    // Check if values list is currently empty.
    $entity_target = $this->getEntityTarget($feed, $entity);
    $is_empty = empty($entity_target->get($field_name)->getValue());

    if (empty($entity_target)) {
      return;
    }

    parent::setTarget($feed, $entity, $field_name, $values);

    $item_list = $entity_target->get($field_name);

    // Append roles from unsaved entity, if there is one.
    if ($entity_target->id() && $is_empty) {
      $original = $this->entityTypeManager->getStorage($entity_target->getEntityTypeId())
        ->loadUnchanged($entity->id());
      if ($original) {
        $original_values = $original->get($field_name)->getValue();

        // Revoke roles, when that option is enabled. But do not touch roles
        // that are not allowed to set by the source.
        if ($this->configuration['revoke_roles']) {
          foreach ($original_values as $key => $value) {
            $rid = $value['target_id'];
            if (!empty($this->configuration['allowed_roles'][$rid])) {
              unset($original_values[$key]);
            }
          }
        }

        // Merge the remaining values.
        $values = $this->mergeRoles($item_list->getValue(), $original_values);

        $item_list->setValue($values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    try {
      return parent::prepareValue($delta, $values);
    }
    catch (ReferenceNotFoundException $e) {
      // Throw an exception with a more understandable message.
      throw new ReferenceNotFoundException($this->t('The role %role cannot be assigned because it does not exist.', [
        '%role' => $values['target_id'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function findEntity(string $field, $search) {
    $entity_id = parent::findEntity($field, $search);
    if ($entity_id !== FALSE) {
      // Check if the role may be assigned.
      if (isset($this->configuration['allowed_roles'][$entity_id]) && !$this->configuration['allowed_roles'][$entity_id]) {
        // This role may *not* be assigned.
        throw new TargetValidationException($this->t('The role %role may not be referenced.', [
          '%role' => $entity_id,
        ]));
      }

      return $entity_id;
    }

    // Automatically create a new role.
    if ($this->configuration['autocreate'] && in_array($this->configuration['reference_by'], [
      'id',
      'label',
    ])) {
      return $this->createRole($search);
    }
  }

  /**
   * Creates a new role with the given label and saves it.
   *
   * @param string $label
   *   The label the new role should get.
   *
   * @return int|string|false
   *   The ID of the new role or false if the given label is empty.
   */
  protected function createRole($label) {
    if (!is_string($label) || !strlen(trim($label))) {
      return FALSE;
    }

    $values = [
      'id' => $this->generateMachineName($label),
      'label' => $label,
    ];
    $entity = $this->entityTypeManager->getStorage($this->getEntityType())->create($values);

    $entity->save();

    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $role_names = array_keys($this->getRoleNames());

    $config = parent::defaultConfiguration() + [
      'allowed_roles' => array_combine($role_names, $role_names),
      'autocreate' => FALSE,
      'revoke_roles' => FALSE,
    ];
    return $config;
  }

  /**
   * Returns a list of role names, keyed by role ID.
   *
   * @return array
   *   A list of role names.
   */
  protected function getRoleNames() {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    return array_map(function ($item) {
      return $item->label();
    }, $roles);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Hack to find out the target delta.
    $delta = 0;
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'target-settings-') === 0) {
        [, , $delta] = explode('-', $key);
        break;
      }
    }

    $form['allowed_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed roles'),
      '#options' => $this->getRoleNames(),
      '#default_value' => $this->configuration['allowed_roles'],
      '#description' => $this->t('Select the roles to accept from the feed.<br />Any other roles will be ignored.'),
    ];
    $form['autocreate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto create'),
      '#description' => $this->t("Create the role if it doesn't exist. This option has only effect when referencing by ID or label."),
      '#default_value' => $this->configuration['autocreate'],
      '#states' => [
        'enabled' => [
          [':input[name="mappings[' . $delta . '][settings][reference_by]"]' => ['value' => 'id']],
          [':input[name="mappings[' . $delta . '][settings][reference_by]"]' => ['value' => 'label']],
        ],
      ],
    ];
    $form['revoke_roles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revoke roles'),
      '#description' => t('If enabled, roles that are not provided by the feed will be revoked for the user. This affects only the "Allowed roles" as configured above.'),
      '#default_value' => $this->configuration['revoke_roles'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    // Allowed roles.
    $role_names = array_intersect_key($this->getRoleNames(), array_filter($this->configuration['allowed_roles']));
    if (empty($role_names)) {
      $role_names = ['<' . $this->t('none') . '>'];
    }
    $summary[] = $this->t('Allowed roles: %roles', ['%roles' => implode(', ', $role_names)]);

    // Autocreate.
    if ($this->configuration['autocreate']) {
      $summary[] = $this->t('Automatically create roles');
    }
    else {
      $summary[] = $this->t('Only assign existing roles');
    }

    // Revoke roles.
    if ($this->configuration['revoke_roles']) {
      $summary[] = $this->t('Revoke roles: yes');
    }
    else {
      $summary[] = $this->t('Revoke roles: no');
    }

    return $summary;
  }

  /**
   * Merge two arrays of user roles together and remove duplicates.
   *
   * @param array $roles
   *   An array of roles.
   * @param array $merging_roles
   *   An array of merging roles.
   *
   * @return array
   *   An array of merged roles.
   */
  protected function mergeRoles(array $roles, array $merging_roles) {
    $merged_roles = array_merge($roles, $merging_roles);

    $existing_map = [];
    $unique_roles = [];

    foreach ($merged_roles as $role) {
      // Ignore role ids that already exist.
      if (isset($existing_map[$role['target_id']])) {
        continue;
      }

      // Add the role and mark the role id as existed.
      $unique_roles[] = $role;
      $existing_map[$role['target_id']] = 1;
    }

    return $unique_roles;
  }

}
