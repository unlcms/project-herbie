<?php

namespace Drupal\unl_contenthub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;

/**
 * Form to trigger the cleanup of major node revisions.
 */
class CleanupMajorRevisionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cleanup_major_revisions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cleanup Major Node Revisions'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'title' => $this->t('Cleaning up Major node revisions'),
      'operations' => [
        [[$this, 'processBatch'], []],
      ],
      'finished' => [$this, 'finishBatch'],
      'init_message' => $this->t('Starting cleanup process...'),
      'progress_message' => $this->t('Processed @current out of @total nodes.'),
      'error_message' => $this->t('An error occurred during processing.'),
    ];
    batch_set($batch);
    if (PHP_SAPI === 'cli') {
      drush_backend_batch_process();
    }
  }

  /**
   * Batch process callback.
   */
  public function processBatch(&$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_nid'] = 0;

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'major')
        ->accessCheck(FALSE);
      $total = $query->count()->execute();

      if ($total == 0) {
        $context['finished'] = 1;
        $context['results']['processed'] = 0;
        $context['message'] = $this->t('No major nodes found to process.');
        return;
      }

      $context['sandbox']['max'] = $total;
      $this->logger('cleanup_major_revisions')->info('Starting batch process for @total major nodes.', ['@total' => $total]);
    }

    // Process nodes in batches of 2.
    $limit = 2;
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'major')
      ->condition('nid', $context['sandbox']['current_nid'], '>')
      ->sort('nid', 'ASC')
      ->range(0, $limit)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($nids)) {
      $context['finished'] = 1;
      return;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('node');

    foreach ($nids as $nid) {
      $node = Node::load($nid);
      if ($node) {
        // Get the current default revision ID.
        $current_vid = $node->getRevisionId();

        // Get all revisions for this node.
        $connection = Database::getConnection();
        $revisions = $connection->select('node_revision', 'nr')
          ->fields('nr', ['vid', 'revision_timestamp'])
          ->condition('nr.nid', $nid)
          ->orderBy('vid', 'ASC')
          ->execute()
          ->fetchAll();

        $timestamp_groups = [];
        foreach ($revisions as $revision) {
          $timestamp_groups[$revision->revision_timestamp][] = $revision->vid;
        }

        // Process each timestamp group.
        foreach ($timestamp_groups as $timestamp => $vids) {
          if (count($vids) > 1) {
            sort($vids);
            $keep_vid = array_shift($vids); // Keep the smallest VID.
            foreach ($vids as $vid_to_delete) {
              if ($vid_to_delete !== $keep_vid && $vid_to_delete !== $current_vid) {
                // Only delete if it's not the current revision.
                try {
                  $revision = $storage->loadRevision($vid_to_delete);
                  if ($revision) {
                    $storage->deleteRevision($revision->getRevisionId());
                    $this->logger('cleanup_major_revisions')->info('Deleted revision @vid for node @nid.', [
                      '@vid' => $vid_to_delete,
                      '@nid' => $nid,
                    ]);
                  }
                }
                catch (\Exception $e) {
                  $this->logger('cleanup_major_revisions')->error('Error deleting revision @vid for node @nid: @message', [
                    '@vid' => $vid_to_delete,
                    '@nid' => $nid,
                    '@message' => $e->getMessage(),
                  ]);
                }
              }
            }
          }
        }

        $context['results']['processed'] = ($context['results']['processed'] ?? 0) + 1;
      }

      $context['sandbox']['progress']++;
      $context['sandbox']['current_nid'] = $nid;
    }

    // Update progress.
    $context['message'] = $this->t('Processed @current of @total major nodes', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);

    // Check if we're done.
    $context['finished'] = $context['sandbox']['progress'] >= $context['sandbox']['max'] ? 1 : $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  /**
   * Batch finished callback.
   */
  public function finishBatch($success, $results, $operations) {
    if ($success) {
      $message = $this->t('Finished processing @count major nodes.', [
        '@count' => $results['processed'] ?? 0,
      ]);
      $this->messenger()->addStatus($message);
      $this->logger('cleanup_major_revisions')->info('Batch process completed successfully. Processed @count nodes.', [
        '@count' => $results['processed'] ?? 0,
      ]);
    }
    else {
      $this->messenger()->addError($this->t('An error occurred during processing. Check the logs for details.'));
      $this->logger('cleanup_major_revisions')->error('Batch process failed.');
    }
  }

}
