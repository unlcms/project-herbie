<?php

namespace Drupal\field_css\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\field_css\Traits\CssTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the CSS formatter.
 *
 * @FieldFormatter(
 *   id = "css",
 *   label = @Translation("CSS"),
 *   field_types = {"css"}
 * )
 */
class CssFormatter extends FormatterBase {

  use CssTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->setCurrentRouteMatch($container->get('current_route_match'));
    return $instance;
  }

  /**
   * Sets $currentRouteMatch property.
   */
  public function setCurrentRouteMatch(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'location' => 'head',
      'prefix' => 'none',
      'fixed_prefix_value' => '',
    ] + parent::defaultSettings();
  }

  /**
   * Returns an array of selector options.
   *
   * @return array
   *   An array of selector options.
   */
  public static function getSelectorOptions() {
    return [
      'none' => 'None',
      'entity-item' => 'EntityType-Id (e.g. scoped-css--node-4938)',
      'fixed-value' => 'Fixed Value',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['location'] = [
      '#type' => 'radios',
      '#title' => $this->t('Location'),
      '#options' => [
        'head' => $this->t('HEAD'),
        'body' => $this->t('BODY'),
      ],
      '#default_value' => $this->getSetting('location'),
    ];

    $form['prefix'] = [
      '#type' => 'select',
      '#title' => $this->t('Selector Prefix'),
      '#default_value' => $this->getSetting('prefix'),
      '#options' => $this->getSelectorOptions(),
      '#required' => TRUE,
      '#description' => $this->t('What selector should prefix all css rules.'),
    ];

    $field_name = $this->fieldDefinition->get('field_name');
    $form['fixed_prefix_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed Prefix Value'),
      '#default_value' => $this->getSetting('fixed_prefix_value'),
      '#element_validate' => [[$this, 'validateFixedPrefixValue']],
      '#description' => $this->t('Provide the fixed value declaration that will prefix all custom declarations. The value should be a valid CSS class without a leading period.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][prefix]"]' => ['value' => 'fixed-value'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Validation method for the fixed_prefix_value field.
   *
   * @param array $element
   *   The element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form array.
   */
  public static function validateFixedPrefixValue(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValues();
    $fixed_prefix_value = NestedArray::getValue($values, $element['#parents']);
    // Use NestedArray::getValue() to also get the value for the 'prefix'
    // field, but replace the final value in the #parents array
    // ('fixed_prefix_value') with 'prefix' to get that value.
    $prefix_value = NestedArray::getValue($values, array_replace(
      $element['#parents'], [(count($element['#parents']) - 1) => 'prefix']
    ));

    if ($prefix_value == 'fixed-value') {
      if (empty($fixed_prefix_value)) {
        $form_state->setError($element, t('A Fixed Prefix Value must be entered.'));
      }
      else {
        if (substr($fixed_prefix_value, 0, 1) === '.') {
          $form_state->setError($element, t('The Fixed Prefix Value must not begin with a period.'));
        }
        // Remove leading period before running through cleanCssIdentifier().
        $fixed_prefix_value = ltrim($fixed_prefix_value, '.');
        if ($fixed_prefix_value != Html::cleanCssIdentifier($fixed_prefix_value)) {
          $form_state->setError($element, t('The Fixed Prefix Value must be a valid CSS class.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Location: @location', ['@location' => strtoupper($this->getSetting('location'))]),
      $this->t('Selector Prefix: @selector', ['@selector' => $this->getSelectorOptions()[$this->getSetting('prefix')]]),
    ];
    if ($this->getSetting('prefix') == 'fixed-value') {
      $summary[] = $this->t('Fixed Prefix Value: @value', ['@value' => $this->getSetting('fixed_prefix_value')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Whether <style> element is rendered in <head> or <body>.
    $location = $this->getSetting('location');
    // If the route is for certain Layout Builder routes, then override the
    // location to 'body'. If the <style> element is rendered in <head>, then
    // CSS changes will not be updated by Layout Builder's live preview
    // feature. This forces an editor reload the page to see CSS changes.
    $routes = [
      'layout_builder.add_block',
      'layout_builder.update_block',
      'layout_builder.remove_block',
      'layout_builder.defaults.*',
      'layout_builder.overrides.*',
    ];
    foreach ($routes as $item) {
      if (preg_match("/$item/", $this->currentRouteMatch->getRouteName())) {
        $location = 'body';
        break;
      }
    }

    $element = [];

    $entity = $items->getEntity();
    $entity_type = $entity->getEntityType();
    $key_prefix = $entity_type->id() . '_' . $entity->id() . '_' . $items->getName();

    foreach ($items as $delta => $item) {
      if ($item->value) {
        $prefix_setting = $this->getSetting('prefix');

        if ($prefix_setting == 'entity-item') {
          $prefix = $this->generatePrefix($entity, TRUE);
          $processed_value = $this->addSelectorPrefix($item->value, $prefix);
        }
        elseif ($prefix_setting == 'fixed-value') {
          $processed_value = $this->addSelectorPrefix($item->value, '.' . $this->getSetting('fixed_prefix_value'));
        }
        else {
          $processed_value = $this->formatCss($item->value);
        }

        if ($location == 'head') {
          $element[$delta]['#attached']['html_head'][] = [
            [
              '#type' => 'html_tag',
              '#tag' => 'style',
              '#value' => $processed_value,
              '#weight' => 100,
            ],
            $key_prefix . '_' . $delta,
          ];
        }
        else {
          $element[$delta]['value'] = [
            '#type' => 'html_tag',
            '#tag' => 'style',
            '#value' => $processed_value,
          ];
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [];
  }

}
