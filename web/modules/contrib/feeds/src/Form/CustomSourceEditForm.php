<?php

namespace Drupal\feeds\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Url;
use Drupal\feeds\FeedTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form for editing a custom source.
 */
class CustomSourceEditForm extends FormBase {

  /**
   * The custom source plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * A FeedsCustomSource plugin.
   *
   * @var \Drupal\feeds\Plugin\Type\CustomSource\CustomSourceInterface
   */
  protected $plugin;

  /**
   * Constructs a new CustomSourceEditForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The custom source plugin manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.feeds.custom_source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_custom_source_edit_form';
  }

  /**
   * Page title callback.
   *
   * @return string
   *   The title of the mapping page.
   */
  public function title(FeedTypeInterface $feeds_feed_type) {
    return $this->t('Custom sources for @label', ['@label' => $feeds_feed_type->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FeedTypeInterface $feeds_feed_type = NULL, $key = NULL) {
    $this->feedType = $feeds_feed_type;
    if (!$this->feedType->customSourceExists($key)) {
      throw new NotFoundHttpException();
    }
    $source = $this->feedType->getCustomSource($key);
    $source['feed_type'] = $feeds_feed_type;
    $type = $source['type'] ?? 'blank';
    $this->plugin = $this->pluginManager->createInstance($type, $source);

    $form['source'] = [
      '#tree' => TRUE,
      '#array_parents' => ['source'],
    ];

    $subform_state = SubformState::createForSubform($form['source'], $form, $form_state);
    $form['source'] = $this->plugin->buildConfigurationForm($form['source'], $subform_state);
    $form['source']['type'] = [
      '#type' => 'value',
      '#value' => $type,
    ];

    $cancel_url = Url::fromRoute('entity.feeds_feed_type.sources', [
      'feeds_feed_type' => $this->feedType->id(),
    ]);
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $cancel_url,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['source'], $form, $form_state);
    $this->plugin->validateConfigurationForm($form['source'], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $source = $form_state->getValue('source');

    $this->feedType->addCustomSource($source['machine_name'], $source);
    $this->feedType->save();

    $this->messenger()->addStatus($this->t('The source %label has been updated on the feed type %feed_type.', [
      '%label' => $source['label'],
      '%feed_type' => $this->feedType->label(),
    ]));
  }

}
