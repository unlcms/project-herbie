<?php

namespace Drupal\layout_builder_styles\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\layout_builder_styles\LayoutBuilderStyleGroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating layout builder style groups.
 */
class LayoutBuilderStyleGroupForm extends EntityForm implements ContainerInjectionInterface {

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
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\layout_builder_styles\LayoutBuilderStyleGroupInterface $group */
    $group = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $group->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $group->id(),
      '#machine_name' => [
        'exists' => '\Drupal\layout_builder_styles\Entity\LayoutBuilderStyleGroup::load',
      ],
      '#disabled' => !$group->isNew(),
    ];

    $form['multiselect'] = [
      '#title' => $this->t('Multiple styles support'),
      '#type' => 'radios',
      '#default_value' => $group->getMultiselect() ?? LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE,
      '#required' => TRUE,
      '#options' => [
        LayoutBuilderStyleGroupInterface::TYPE_SINGLE => $this->t('User may only apply one style from this group per per section or block.'),
        LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE => $this->t('User may apply multiple styles from this group per section or block.'),
      ],
    ];

    $form['form_type'] = [
      '#title' => $this->t('Form element for multiple styles'),
      '#type' => 'radios',
      '#default_value' => $group->getFormType() ?? LayoutBuilderStyleGroupInterface::TYPE_CHECKBOXES,
      '#description' => $this->t('Determines whether the styles selector should display as multiple checkboxes or a select (multiple) box.'),
      '#required' => TRUE,
      '#options' => [
        LayoutBuilderStyleGroupInterface::TYPE_CHECKBOXES => $this->t('Checkboxes'),
        LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE_SELECT => $this->t('Select (multiple) box'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="multiselect"]' => ['value' => 'multiple'],
        ],
      ],
    ];

    $form['required'] = [
      '#title' => $this->t('Require Selection'),
      '#type' => 'checkbox',
      '#default_value' => $group->getRequired(),
      '#description' => $this->t('Choose whether the group requires a style be selected by the user.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $group = $this->entity;
    $status = $group->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addStatus($this->t('Created the %label style group.', [
          '%label' => $group->label(),
        ]));
        break;

      default:
        $this->messenger->addStatus($this->t('Saved the %label style group.', [
          '%label' => $group->label(),
        ]));
    }
    $form_state->setRedirectUrl($group->toUrl('collection'));
  }

}
