<?php

namespace Drupal\unl_breadcrumbs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure unl_breadcrumbs settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'unl_breadcrumbs.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_breadcrumbs_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['markup'] = [
      '#markup' => $this->t('When UNL Breadcrumbs is enabled, breadcrumbs must follow a prescribed pattern: "Nebraska > [Site Root Breadcrumb Title] > [Parent Items] > [Page Title]".<br>Configurable options can be managed below:'),
    ];

    $form['site_root_breadcrumb_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Root Breadcrumb Title'),
      '#description' => $this->t('The title used for this site in breadcrumbs. E.g. "Nebraska > [Site Root Breadcrumb Title] > [Page Title]". In the instance of the flagship site, this setting will be empty.'),
      '#default_value' => $config->get('site_root_breadcrumb_title'),
      '#states' => [
        'disabled' => [
          ':input[name="site_root_breadcrumb_title_use_site_name"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['site_root_breadcrumb_title_use_site_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Site Name for Site Root Breadcrumb Title'),
      '#description' => $this->t('If checked, the Site Name will be used instead of a custom title.'),
      '#default_value' => $config->get('site_root_breadcrumb_title_use_site_name'),
    ];

    $form['interstitial_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Interstitial Breadcrumbs'),
    ];
    $form['interstitial_wrapper']['markup'] = [
      '#markup' => $this->t('Interstitial breadcrumbs allow an organizational hierarchy back to www.unl.edu in the event this site is not a child of www.unl.edu. For example, if the Center for Excellent Examples, which is part of the College of Examples, has its own website, then the default breadcrumbs would be "Nebraska > Center for Excellent Examples" instead of "Nebraska > College of Examples > Center for Excellent Examples". Interstitial breadcrumbs allow missing breadcrumbs to be added for a complete hierarchy. Interstitial breadcrumbs are inserted between "Nebraska" and the site root breadcrumb.'),
    ];
    $form['interstitial_wrapper']['interstitial'] = [
      '#type' => 'table',
      '#prefix' => '<div id="interstitial-wrapper">',
      '#suffix' => '</div>',
      '#header' => [
        '',
        $this->t('Title'),
        $this->t('URL'),
        $this->t('Weight'),
        $this->t('Actions'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'interstitial-order-weight',
        ],
      ],
    ];

    if ($form_state->isRebuilding()) {
      $interstitial_config = $form_state->getValue('interstitial');
    }
    else {
      $interstitial_config = $config->get('interstitial_breadcrumbs');
    }

    foreach ($interstitial_config as $delta => $item) {
      // TableDrag: Weight column element.
      $form['interstitial_wrapper']['interstitial'][$delta]['#attributes']['class'][] = 'draggable';
      $form['interstitial_wrapper']['interstitial'][$delta]['#weight'] = isset($delta) ? $delta : 0;
      $form['interstitial_wrapper']['interstitial'][$delta]['drag'] = [
        '#markup' => '',
      ];
      $form['interstitial_wrapper']['interstitial'][$delta]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#title_display' => 'invisible',
        '#description' => $this->t('*Required field'),
        '#size' => NULL,
        '#default_value' => ($item['title']) ? $item['title'] : '',
        '#required' => TRUE,
      ];
      $form['interstitial_wrapper']['interstitial'][$delta]['url'] = [
        '#type' => 'url',
        '#title' => $this->t('URL'),
        '#title_display' => 'invisible',
        '#description' => $this->t('*Required field'),
        '#size' => NULL,
        '#default_value' => ($item['url']) ? $item['url'] : '',
        '#required' => TRUE,
      ];
      $form['interstitial_wrapper']['interstitial'][$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for interstitial item @delta', ['@delta' => $delta]),
        '#title_display' => 'invisible',
        '#attributes' => ['class' => ['interstitial-order-weight']],
        '#default_value' => isset($delta) ? $delta : 0,
      ];
      $form['interstitial_wrapper']['interstitial'][$delta]['delete'] = [
        '#type' => 'submit',
        '#title' => $this->t('Remove'),
        '#name' => 'delete_' . $delta,
        '#value' => 'Remove',
        '#submit' => ['::ajaxSubmit'],
        '#ajax' => [
          'callback' => '::addMoreSet',
          'wrapper' => 'interstitial-wrapper',
        ],
      ];
    }

    $form['interstitial_wrapper']['add'] = [
      '#type' => 'submit',
      '#title' => $this->t('Add interstitial breadcrumb'),
      '#value' => $this->t('Add more'),
      '#submit' => ['::ajaxSubmit'],
      '#ajax' => [
        'callback' => '::addMoreSet',
        'wrapper' => 'interstitial-wrapper',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Callback function to add a row to interstitial table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The interstitial portion of the $form array.
   */
  public function addMoreSet(array &$form, FormStateInterface $form_state) {
    return $form['interstitial_wrapper']['interstitial'];
  }

  /**
   * Callback submit function to add/remove rows to interstitial table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $items = $form_state->getValue('interstitial');
    if (empty($items)) {
      $items = [];
    }
    $parents = $form_state->getTriggeringElement()['#parents'];
    if (isset($parents[0]) && $parents[0] == 'add') {
      $items[] = [
        'title' => '',
        'url' => '',
        'weight' => 0,
      ];
    }
    if (isset($parents[2]) && $parents[2] == 'delete') {
      unset($items[$parents[1]]);
    }

    $form_state->setValue('interstitial', $items);
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process intersticial breadcrumb items.
    $interstitial_items = $form_state->getValue('interstitial');
    $interstitial = [];
    foreach ($interstitial_items as $item) {
      $interstitial[] = [
        'title' => $item['title'],
        'url' => $item['url'],
      ];
    }

    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('site_root_breadcrumb_title', $form_state->getValue('site_root_breadcrumb_title'))
      ->set('site_root_breadcrumb_title_use_site_name', $form_state->getValue('site_root_breadcrumb_title_use_site_name'))
      ->set('interstitial_breadcrumbs', $interstitial)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
