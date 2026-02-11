<?php

namespace Drupal\unl_bulk_file_remover\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\file\Entity\File;

/**
 * Implements an bulk file remover form.
 */
class BulkFileRemoverForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_bulk_file_remover_1';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['question'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>Clicking "Delete" will remove unused application/pdf files uploaded on or after the selected date.</p>'),
    ];

    $form['file_upload_date'] = [
      '#type' => 'date',
      '#required' => TRUE,
      '#title' => $this->t('File upload date'),
      '#description' => $this->t('Select the upload date. Unused application/pdf files uploaded on or after this date will be deleted.'),
      '#attributes' => [
        'max' => date('Y-m-d'),
      ],
    ];

    // Delete button that wil start the process.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $date_string = $form_state->getValue('file_upload_date');
    try {
      $submitted = DrupalDateTime::createFromFormat('Y-m-d', $date_string);
      $submitted->setTime(0, 0, 0);

      $today = new DrupalDateTime('today');

      if ($submitted > $today) {
        $form_state->setErrorByName(
          'file_upload_date',
          $this->t('The date cannot be today or in the future. Please select an earlier date.')
        );
      }
    } catch (\Exception $e) {
      $form_state->setErrorByName('file_upload_date', $this->t('Invalid date format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Date string 'YYYY-MM-DD'
    $date_string = $form_state->getValue('file_upload_date');
    $start_timestamp = strtotime($date_string . ' 00:00:00');

    $fids = \Drupal::entityQuery('file')
      ->condition('created', $start_timestamp, '>=')
      ->condition('filemime', 'application/pdf')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();
    $count_deleted = 0;
    if (!empty($fids)) {
      $files = \Drupal\file\Entity\File::loadMultiple($fids);
      foreach ($files as $file) {
        $usage = \Drupal::service('file.usage')->listUsage($file);
        // If the file is associated with a media entity, then it is considered "used" and will not be deleted.
        if (empty($usage)) {
          $file->delete();
          $count_deleted++;
        }
      }
    }

    $this->messenger()->addStatus($this->t('The selected date is @date and @count application/pdf files were deleted.', ['@date' => $date_string, '@count' => $count_deleted]));
  }
}
