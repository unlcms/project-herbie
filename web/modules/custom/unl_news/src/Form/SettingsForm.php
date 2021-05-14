<?php

namespace Drupal\unl_news\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The settings form for the Twig UI module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'unl_news.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_news_settings';
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

    // Temp until Nebraska Today API is updated.
    $options = [
      '20861' => 'Riko Bishop',
      '31417' => '2021 spring commencement',
      '119' => 'Tom Osborne',
      '520' => 'graduation',
      '17' => 'commencement',
      '2795' => 'Jennifer Clark',
      '32877' => 'Connie Clifton Rath',
      '15922' => 'Grit and Glory',
    ];
    asort($options, SORT_STRING | SORT_FLAG_CASE);

    $form['tag_ids'] = [
      '#type' => 'select',
      '#title' => $this->t('Tags'),
      '#description' => $this->t('Nebraska Today tags to be imported by this site.'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $config->get('tag_ids'),
    ];

    $form['markup'] = [
      '#markup' => 'Test',
    ];

    // @todo provide ability to process on demand.
    // @todo show items in queue.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tag_ids = $form_state->getValue('tag_ids');
    $tag_ids = array_keys($tag_ids);

    $config = $this->configFactory->getEditable(static::SETTINGS)
      ->set('tag_ids', $tag_ids);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
