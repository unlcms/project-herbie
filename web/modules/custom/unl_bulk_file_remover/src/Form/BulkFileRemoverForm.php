<?php

namespace Drupal\unl_bulk_file_remover\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Batch\BatchBuilder;

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

    $form['batch_size'] = [
      '#type' => 'number',
      '#required' => TRUE,
      '#title' => $this->t('Batch size'),
      '#description' => $this->t('Number of files to process in each batch. Must be between 1 and 100.'),
      '#default_value' => 3,
      '#min' => 1,
      '#max' => 100,
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
    $batch_size = $form_state->getValue('batch_size');

    $fids = \Drupal::entityQuery('file')
      ->condition('created', $start_timestamp, '>=')
      ->condition('filemime', 'application/pdf')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $fids_count = count($fids);
    $batch = new BatchBuilder();

    $batch
      ->setTitle(t('Processing @total entities', ['@total' => $fids_count]))
      ->setInitMessage(t('Starting deletion...'))
      ->setProgressMessage(t('Processing @current of @total files.'))
      ->setErrorMessage(t('Error during deletion.'))
      ->setFinishCallback([self::class, 'finishDeletion'])
      ->addOperation([self::class, 'deleteChunk'], [$fids, $batch_size, $fids_count]);

    batch_set($batch->toArray());

    $this->messenger()->addStatus($this->t('The selected date is @date and @count application/pdf files were deleted.', ['@date' => $date_string, '@count' => $count_deleted]));
  }


  /**
   * Batch operation callback.
   *
   * @param int $fids_count
   *   Total number of ids.
   * @param int $batch_size
   *   How many to process per run.
   * @param array $context
   *   Batch context.
   */
  public static function deleteChunk(array $fids, int $batch_size, int $fids_count, &$context) {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max']      = $fids_count;
      $context['sandbox']['all_ids']  = $fids; // Store the full list once
      $context['results'] = [];
    }

    // process one chunk.
    $offset = $context['sandbox']['progress'];
    // Get the next chunk of IDs to process
    $chunk_ids = array_slice($fids, $offset, $batch_size);

    // If there are no more IDs to process, we're finished.
    if (empty($chunk_ids)) {
      $context['finished'] = 1;
      return;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('file');
    $files   = $storage->loadMultiple($chunk_ids);
    $usage_service = \Drupal::service('file.usage');
    foreach ($files as $file) {
      $fid = $file->id();

      try {
        $usages = $usage_service->listUsage($file);

        if (empty($usages)) {
          $file->delete();
          $context['results']['deleted'][] = $fid;
        } else {
          $context['results']['skipped'][] = $fid;
        }
      } catch (\Exception $e) {
        $context['results']['errors'][] = "FID $fid: " . $e->getMessage();
      }
    }

    // Update progress and message.
    $deleted_count = count($chunk_ids);

    // Update the progress with the number of items processed in this chunk
    $context['sandbox']['progress'] += $deleted_count;

    $context['message'] = t('Processing (@start–@end of @total)', [
      '@count' => $deleted_count,
      '@start' => $offset + 1,
      '@end'   => $offset + $deleted_count,
      '@total' => $context['sandbox']['max'],
    ]);

    // Tell API how much of the batch is completed. This is a value between 0 and 1.
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];

    // If we processed fewer items than the batch size, it means we're at the end of the list and we can mark the batch as finished.
    if ($deleted_count < $batch_size) {
      $context['finished'] = 1;
    }
  }


  public static function finishDeletion($success, $results, $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $deleted_count = count($results['deleted'] ?? []);
      $skipped_count = count($results['skipped'] ?? []);
      $messenger->addStatus(t('Batch complete! Deleted @deleted files. Skipped @skipped files (still in use).', [
        '@deleted' => $deleted_count,
        '@skipped' => $skipped_count,
      ]));
    }
    else {
      // Error case
      $messenger->addError(t('The batch failed. Some operations may not have completed.'));
    }
  }
}
