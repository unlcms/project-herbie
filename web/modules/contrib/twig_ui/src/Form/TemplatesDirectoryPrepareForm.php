<?php

namespace Drupal\twig_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\twig_ui\TemplateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to trigger directory preparation.
 */
class TemplatesDirectoryPrepareForm extends FormBase {

  /**
   * The Template Manager.
   *
   * @var \Drupal\twig_ui\TemplateManager
   */
  protected $templateManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   *
   * @param \Drupal\twig_ui\TemplateManager $template_manager
   *   The Template Manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(TemplateManager $template_manager, MessengerInterface $messenger) {
    $this->templateManager = $template_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig_ui.template_manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twig_ui_templates_directory_prepare_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    require_once __DIR__ . '/../../twig_ui.install';
    $requirements = twig_ui_requirements('runtime');
    if ($requirements['twig_ui_templates']['severity'] == REQUIREMENT_ERROR) {
      $form['message'] = [
        '#markup' => $requirements['twig_ui_templates']['description'],
        // Simulate warning message.
        '#prefix' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['message'] = [
        '#markup' => $requirements['twig_ui_templates']['value'],
        // Simulate status message.
        '#prefix' => '<div role="contentinfo" aria-label="Status message" class="messages messages--status">',
        '#suffix' => '</div>',
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Prepare templates directory'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = $this->templateManager->prepareTemplatesDirectory();
    if ($result === TRUE) {
      $this->messenger->addStatus($this->t('The Twig UI templates directory was successfully created and protected.'));
    }
    else {
      $this->messenger->addWarning($this->t('Preparation of the Twig UI templates directory resulted in the following error: @message', ['@message' => $result]));
    }
  }

}
