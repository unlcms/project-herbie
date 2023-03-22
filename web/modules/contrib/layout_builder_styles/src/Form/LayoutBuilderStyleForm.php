<?php

namespace Drupal\layout_builder_styles\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\layout_builder_styles\LayoutBuilderStyleInterface;
use Drupal\layout_builder_styles\Entity\LayoutBuilderStyleGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating layout builder styles.
 */
class LayoutBuilderStyleForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The layout manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a LayoutBuilderStyleForm object.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_manager
   *   The layout plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(BlockManagerInterface $blockManager, LayoutPluginManagerInterface $layout_manager, MessengerInterface $messenger) {
    $this->blockManager = $blockManager;
    $this->layoutManager = $layout_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.core.layout'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    // Prevent actions from rendering if there's no groups defined yet.
    $groups = LayoutBuilderStyleGroup::loadMultiple();
    if (empty($groups)) {
      return [];
    }
    else {
      return parent::actionsElement($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $groups = LayoutBuilderStyleGroup::loadMultiple();
    if (empty($groups)) {
      $caption = '<p>' . $this->t(
          'You must <a href="@group_url">create at least one group</a> before creating a style.',
          ['@group_url' => Url::fromRoute('entity.layout_builder_style_group.add_form')->toString()]
        ) . '</p>';
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    $form = parent::form($form, $form_state);

    /** @var \Drupal\layout_builder_styles\Entity\LayoutBuilderStyle $style */
    $style = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $style->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $style->id(),
      '#machine_name' => [
        'exists' => '\Drupal\layout_builder_styles\Entity\LayoutBuilderStyle::load',
      ],
      '#disabled' => !$style->isNew(),
    ];

    $form['classes'] = [
      '#title' => $this->t('CSS classes'),
      '#type' => 'textarea',
      '#default_value' => $style->getClasses(),
      '#description' => $this->t('Enter one per line.'),
      '#required' => TRUE,
    ];

    // For now we only support block styles.
    $form['type'] = [
      '#title' => $this->t('Type'),
      '#type' => 'radios',
      '#default_value' => $style->getType(),
      '#description' => $this->t('Determines if this style can be applied to sections or blocks.'),
      '#required' => TRUE,
      '#options' => [
        LayoutBuilderStyleInterface::TYPE_COMPONENT => $this->t('Block'),
        LayoutBuilderStyleInterface::TYPE_SECTION => $this->t('Section'),
      ],
    ];

    $groupOptions = [];
    foreach ($groups as $group) {
      $groupOptions[$group->id()] = $group->label();
    }
    $form['group'] = [
      '#title' => $this->t('Group'),
      '#type' => 'radios',
      '#default_value' => $style->getGroup(),
      '#description' => $this->t('Determines the group of this style.'),
      '#required' => TRUE,
      '#options' => $groupOptions,
    ];

    $blockDefinitions = $this->blockManager->getDefinitions();
    $blockDefinitions = $this->blockManager->getGroupedDefinitions($blockDefinitions);

    // Remove individual reusable blocks from list.
    unset($blockDefinitions['Custom']);

    if (isset($blockDefinitions['Inline blocks'])) {
      // Relabel the inline block type listing as generic "Custom block types".
      // This category will apply to inline blocks & reusable blocks.
      $blockDefinitions['Custom block types'] = $blockDefinitions['Inline blocks'];
      unset($blockDefinitions['Inline blocks']);
      ksort($blockDefinitions);
    }

    $form['block_restrictions'] = [
      '#type' => 'details',
      '#title' => $this->t('Block restrictions'),
      '#description' => $this->t('Optionally limit this style to the following block(s).'),
      '#states' => [
        'visible' => [
          'input[name="type"]' => ['value' => LayoutBuilderStyleInterface::TYPE_COMPONENT],
        ],
      ],
    ];

    foreach ($blockDefinitions as $category => $blocks) {
      $category_form = [
        '#type' => 'details',
        '#title' => $category,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      foreach ($blocks as $blockId => $block) {
        $machine_name = $blockId;
        $category_form[$blockId] = [
          '#type' => 'checkbox',
          '#title' => $block['admin_label'] . ' <small>(' . $machine_name . ')</small>',
          '#default_value' => in_array($blockId, $style->getBlockRestrictions()),
          '#parents' => [
            'block_restrictions',
            $blockId,
          ],
        ];
        if ($category == 'Custom block types') {
          $machine_name = str_replace('inline_block:', '', $machine_name);
          $category_form[$blockId]['#title'] = $block['admin_label'] . ' <small>(' . $machine_name . ')</small>';
          $category_form[$blockId]['#description'] = $this->t('Block type selections effect both re-usable and inline blocks.');
        }
      }
      $form['block_restrictions'][$category] = $category_form;
    }

    $form['layout_restrictions'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout restrictions'),
      '#description' => $this->t('Optionally limit this style to the following layout(s).'),
      '#states' => [
        'visible' => [
          'input[name="type"]' => ['value' => LayoutBuilderStyleInterface::TYPE_SECTION],
        ],
      ],
    ];
    $section_definitions = $this->layoutManager->getFilteredDefinitions('layout_builder', []);
    /** @var \Drupal\Core\Layout\LayoutDefinition $definition */
    foreach ($section_definitions as $section_id => $definition) {
      $form['layout_restrictions'][$section_id] = [
        '#type' => 'checkbox',
        '#title' => $definition->getLabel(),
        '#default_value' => in_array($section_id, $style->getLayoutRestrictions()),
        '#parents' => [
          'layout_restrictions',
          $section_id,
        ],
        '#description' => [
          $definition->getIcon(60, 80, 1, 3),
          [
            '#type' => 'container',
            '#children' => $definition->getLabel() . ' (' . $section_id . ')',
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\layout_builder_styles\Entity\LayoutBuilderStyle
     * $entity
     */
    $entity = parent::buildEntity($form, $form_state);

    // We need to convert the individual checkbox values that were submitted
    // in the form to a single array containing all the block plugin IDs that
    // were checked.
    $blockRestrictions = $form_state->getValue('block_restrictions');
    $blockRestrictions = array_keys(array_filter($blockRestrictions));
    $entity->set('block_restrictions', $blockRestrictions);

    $layoutRestrictions = $form_state->getValue('layout_restrictions');
    $layoutRestrictions = array_keys(array_filter($layoutRestrictions));
    $entity->set('layout_restrictions', $layoutRestrictions);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $style = $this->entity;
    $status = $style->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addStatus($this->t('Created the %label style.', [
          '%label' => $style->label(),
        ]));
        break;

      default:
        $this->messenger->addStatus($this->t('Saved the %label style.', [
          '%label' => $style->label(),
        ]));
    }
    $form_state->setRedirectUrl($style->toUrl('collection'));
  }

}
