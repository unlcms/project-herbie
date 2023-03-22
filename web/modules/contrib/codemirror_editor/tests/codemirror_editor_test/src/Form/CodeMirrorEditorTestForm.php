<?php

namespace Drupal\codemirror_editor_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Codemirror test form.
 */
final class CodeMirrorEditorTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'codemirror_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['editor_1'] = [
      '#type' => 'codemirror',
      '#title' => $this->t('Editor 1'),
      '#rows' => 15,
    ];

    $form['editor_2'] = [
      '#type' => 'codemirror',
      '#title' => $this->t('Editor 2'),
      '#codemirror' => [
        'modeSelect' => [
          'text/html' => $this->t('HTML'),
          'javascript' => $this->t('JavaScript'),
          'css' => $this->t('CSS'),
        ],
        'lineWrapping' => TRUE,
        'lineNumbers' => TRUE,
        'autoCloseTags' => FALSE,
        'styleActiveLine' => TRUE,
        'buttons' => [
          'bold',
          'italic',
          'underline',
        ],
      ],
    ];

    $form['editor_3'] = [
      '#type' => 'codemirror',
      '#title' => $this->t('Editor 3'),
      '#default_value' => "<div>\n  Test\n</div>",
      '#codemirror' => [
        'toolbar' => FALSE,
        'readOnly' => TRUE,
        'height' => 100,
        'foldGutter' => TRUE,
        'mode' => 'text/html',
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

}
