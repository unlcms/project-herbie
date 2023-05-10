<?php

namespace Drupal\field_css\Plugin\Field\FieldWidget;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Sabberworm\CSS\Parser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the CSS field widget.
 *
 * @FieldWidget(
 *   id = "css",
 *   label = @Translation("CSS"),
 *   field_types = {"css"},
 * )
 */
class CssWidget extends WidgetBase {

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
    $instance->setModuleHandler($container->get('module_handler'));
    return $instance;
  }

  /**
   * Sets $moduleHandler property.
   */
  public function setModuleHandler(ModuleHandler $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];
    if (\Drupal::moduleHandler()->moduleExists('codemirror_editor')) {
      $settings = [
        'toolbar' => TRUE,
        'buttons' => self::getAvailableButtons(),
      ];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textarea',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#description' => $this->t('The :root selector cannot be used.'),
      '#element_validate' => [
        [static::class, 'validate'],
      ],
    ];

    if ($this->moduleHandler->moduleExists('codemirror_editor')) {
      $element['value']['#codemirror'] = [
        'mode' => 'text/css',
        'lineNumbers' => TRUE,
        'toolbar' => $this->getSetting('toolbar'),
        'buttons' => $this->getSetting('buttons'),
      ];
    }

    return $element;
  }

  /**
   * Validate the CSS field.
   *
   * @param array $element
   *   The element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validate(array $element, FormStateInterface $form_state) {
    $parser = new Parser($element['#value']);
    $css_document = $parser->parse();
    foreach ($css_document->getAllDeclarationBlocks() as $block) {
      foreach ($block->getSelectors() as $selector) {
        if (strpos($selector->getSelector(), ':root') !== FALSE) {
          $form_state->setError($element, t('The :root selector cannot be used.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return isset($violation->arrayPropertyPath[0]) ? $element[$violation->arrayPropertyPath[0]] : $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      if ($value['value'] === '') {
        $values[$delta]['value'] = NULL;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    if ($this->moduleHandler->moduleExists('codemirror_editor')) {
      $settings = $this->getSettings();
      $form['toolbar'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Load toolbar'),
        '#default_value' => $settings['toolbar'],
      ];

      $form['buttons'] = [
        '#type' => 'select',
        '#multiple' => TRUE,
        '#title' => $this->t('Toolbar buttons'),
        '#default_value' => $settings['buttons'],
        '#options' => array_combine(self::getAvailableButtons(), self::getAvailableButtons()),
        '#value_callback' => [static::class, 'setButtonsValue'],
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][toolbar]"]' => ['checked' => TRUE],
          ],
        ],
        '#description' => $this->t('Buttons that will be available inside the toolbar.'),
      ];

      return $form;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->moduleHandler->moduleExists('codemirror_editor')) {
      $settings = $this->getSettings();
      $summary[] = $this->t('Load toolbar: @toolbar', ['@toolbar' => $this->formatBoolean('toolbar')]);
      if ($settings['toolbar']) {
        $summary[] = $this->t('Toolbar buttons: @buttons', ['@buttons' => implode(", ", $settings['buttons'])]);
      }
    }

    return $summary;
  }

  /**
   * Returns a list of buttons available for CodeMirror.
   *
   * @return array
   *   A list of buttons.
   */
  public static function getAvailableButtons() {
    return [
      'undo',
      'redo',
      'enlarge',
      'shrink',
    ];
  }

  /**
   * Value callback for CodeMirror buttons.
   *
   * Prevent buttons from being stored in config with keyed values.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param mixed $input
   *   The incoming input to populate the form element. If this is FALSE,
   *   the element's default value should be returned.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return mixed
   *   The value to assign to the element.
   */
  public static function setButtonsValue(array &$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return isset($element['#default_value']) ? $element['#default_value'] : [];
    }
    return $input;
  }

  /**
   * Returns formatted boolean setting value.
   *
   * @param string $key
   *   Plugin setting key to format.
   *
   * @return string
   *   Format settings value.
   */
  protected function formatBoolean($key) {
    return $this->settings[$key] ? $this->t('Yes') : $this->t('No');
  }

}
