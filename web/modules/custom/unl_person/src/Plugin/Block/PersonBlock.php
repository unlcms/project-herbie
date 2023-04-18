<?php

namespace Drupal\unl_person\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides a Person List block.
 *
 * @Block(
 *   id = "unl_person_list",
 *   admin_label = @Translation("People"),
 *   category = @Translation("Curated Lists"),
 * )
 */
class PersonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'persons' => [],
      'view_mode' => 'teaser',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // The original 'select' type that doesn't allow custom sorting.
    //    $person_nodes = \Drupal::entityTypeManager()->getStorage('node')
    //      ->loadByProperties(['type' => 'person', 'status' => 1]);
    //    $options = [];
    //    foreach ($person_nodes as $node) {
    //      $options[$node->id()] = $node->label();
    //    }
    //
    //    $form['persons'] = [
    //      '#type' => 'select',
    //      '#title' => $this->t('Persons to display'),
    //      '#required' => TRUE,
    //      '#multiple' => TRUE,
    //      '#default_value' => $this->configuration['persons'],
    //      '#options' => $options,
    //    ];

    // Using entity_autocomplete. Not finished and doesn't allow easy sorting. Needs work on #default_value.
    //     $form['persons'] = [
    //       '#title' => $this->t('Persons to reference'),
    //       '#type' => 'entity_autocomplete',
    //       '#target_type' => 'node',
    //       '#tags' => TRUE,
    //       //'#default_value' => $this->configuration['persons'],
    //       '#selection_handler' => 'default',
    //       '#selection_settings' => [
    //         'target_bundles' => ['person'],
    //        ],
    //     ];

    $form['persons'] = [
      '#type' => 'select2',
      '#title' => $this->t('Person nodes to display'),
      '#description' => $this->t('A person needs to exist as a <a href="/admin/content" target="_blank">Person node on the site</a> to be referenced here. People can be created through the <a href="/node/add" target="_blank">Add content</a> page.'),
      '#default_value' => $this->configuration['persons'],
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#autocomplete' => TRUE,
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['person'],
       ],
     ];

    $view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle('node', 'person');
    unset($view_modes['default']);

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#default_value' => $this->configuration['view_mode'],
      '#options' => $view_modes,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['view_mode'] = $form_state->getValue('view_mode');

    // Can't use $form_state->getValue('persons') as it gives the values sorted
    // in a numerically ascending manner, not respecting user sorting.
    $this->configuration['persons'] = $form_state->getCompleteFormState()->getUserInput()['settings']['persons'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'unl_person_person_list',
      '#attributes' => new Attribute(
        [
          'class' => [
            'unl-person-list',
          ],
        ],
      ),
      '#nodes' => $this->configuration['persons'],
      '#view_mode' => $this->configuration['view_mode'],
    ];
  }
}
