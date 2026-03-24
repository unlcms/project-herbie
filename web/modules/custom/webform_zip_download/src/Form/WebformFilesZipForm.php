<?php

namespace Drupal\webform_zip_download\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Implements an webform files zip form.
 */
class WebformFilesZipForm extends FormBase {

  private $path;

  protected $webform;

  const TEMP_DIR_PREFIX = 'wf_zip_webform_download_';


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_zip_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WebformInterface $webform = null) {
    $this->webform = $webform;
    \Drupal::messenger()->addWarning($this->t('Once you click the download button, your file will begin downloading. Please check your browser’s download list before clicking again.'));

    $form['question'] = [
      '#type' => 'markup',
      '#markup' => $this->t(
        '<p>Clicking “Download” will download all uploaded files submitted through the %webform webform as a ZIP archive.</p>',
        ['%webform' => $webform->label()]
      )
    ];

    // Delete button that wil start the process.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download all uploaded files as ZIP'),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  /**
   * Downloads all uploaded files for a webform as a ZIP archive.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $webform = $this->webform;
    $temp_filename = NULL;

    // Increase PHP timeout for large ZIP creation (10 minutes).
    set_time_limit(600);

    try {
      // Verify access and webform exists.
      if (!$webform || !$webform->access('results')) {
        $this->logger('webform_zip_download')->warning('Unauthorized ZIP download attempt for webform.');
        throw new NotFoundHttpException();
      }

      $webform_id = $webform->id();
      $user = \Drupal::currentUser();

      $this->path = \Drupal::service('settings')->get('file_private_path');

      if ($this->path === FALSE || !is_dir($this->path)) {
        $this->logger('webform_zip_download')->error('Private file path not configured or inaccessible.');
        $this->messenger()->addError($this->t('Webform file system is not configured or inaccessible.'));
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      // Resolve to real path and validate it's within expected directory.
      $real_path = realpath($this->path);
      if ($real_path === FALSE) {
        $this->logger('webform_zip_download')->error('Could not resolve private file path.');
        $this->messenger()->addError($this->t('Webform file system is not configured or inaccessible.'));
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      $form_sub_folder = $real_path . '/webform/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $webform_id);

      if (!is_dir($form_sub_folder)) {
        $this->messenger()->addWarning($this->t('No uploaded files directory found for this webform.'));
        $this->logger('webform_zip_download')->info('ZIP download for webform @webform with no files.', ['@webform' => $webform_id]);
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      // Verify folder is not a symlink
      if (is_link($form_sub_folder)) {
        $this->logger('webform_zip_download')->warning('Symlink detected in webform directory path');
        $this->messenger()->addError($this->t('Invalid webform directory configuration.'));
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      // We may need to also add this to cron jobs to prevent orphaned temp files from accumulating.
      $this->cleanupOrphanedTempFiles();
      $temp_filename = $this->createSecureTempFile();
      if ($temp_filename === FALSE) {
        $this->logger('webform_zip_download')->error('Failed to create temporary ZIP file.');
        $this->messenger()->addError($this->t('Unable to create temporary ZIP file.'));
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      $zip = new ZipArchive();
      if ($zip->open($temp_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        $this->logger('webform_zip_download')->error('Failed to open ZIP archive: @file', ['@file' => $temp_filename]);
        $this->cleanupTempFile($temp_filename);
        $this->messenger()->addError($this->t('Unable to create temporary ZIP file.'));
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      // Recursively add all files and directories to the ZIP with security checks.
      $file_count = 0;
      $total_size = 0;

      try {
        $iterator = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($form_sub_folder, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
          /** @var \SplFileInfo $item */
          $real_folder_path = realpath($item->getPathname());

          // Verify resolved path is actually within the submission folder.
          if ($real_folder_path === FALSE || strpos($real_folder_path, $form_sub_folder) !== 0) {
            $this->logger('webform_zip_download')->warning('Path traversal attempt detected');
            continue;
          }

          // Reject symlinks.
          if (is_link($item->getPathname())) {
            $this->logger('webform_zip_download')->warning('Symlink in webform upload directory ignored');
            continue;
          }

          if ($item->isDir()) {
            $zip->addEmptyDir($this->getRelativePath($real_folder_path, $form_sub_folder, $webform_id));
          } else {
            $file_size = $item->getSize();
            $total_size += $file_size;
            $file_count++;


            // Verify file is readable before adding.
            if (!is_readable($real_folder_path)) {
              $this->logger('webform_zip_download')->warning('File not readable: @file', ['@file' => $real_folder_path]);
              continue;
            }

            $zip->addFile($real_folder_path, $this->getRelativePath($real_folder_path, $form_sub_folder, $webform_id));
          }
        }
      } catch (\Exception $e) {
        $this->logger('webform_zip_download')->error('Error iterating webform files: @error', ['@error' => $e->getMessage()]);
        $zip->close();
        $this->cleanupTempFile($temp_filename);
        $this->messenger()->addError($this->t('An error occurred while processing files.'));
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }
      if ($zip->close() !== TRUE) {
        $this->logger('webform_zip_download')->error('Failed to close ZIP archive properly.');
        $this->cleanupTempFile($temp_filename);
        $this->messenger()->addError($this->t('An error occurred while creating the ZIP archive.'));
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      // Check if ZIP actually contains anything meaningful.
      $zip_size = filesize($temp_filename);
      if ($zip_size === FALSE || $zip_size <= 100) {
        // Empty or near-empty ZIP.
        $this->cleanupTempFile($temp_filename);
        $this->messenger()->addWarning($this->t('No files were found in the webform directory.'));
        $this->logger('webform_zip_download')->info('ZIP download for webform @webform found no eligible files.', ['@webform' => $webform_id]);
        $form_state->setRedirect('webform_zip_download.download_zip', ['webform' => $webform_id]);
        return;
      }

      // Log the download event for audit purposes.
      $this->logger('webform_zip_download')->info('Webform submission files downloaded as ZIP for webform @webform by user @user (files: @count, size: @size bytes)',
        [
          '@webform' => $webform_id,
          '@user' => $user->id(),
          '@count' => $file_count,
          '@size' => $zip_size,
        ]
      );

      // Generate filename with better uniqueness (include timestamp and user ID to prevent collisions).
      $filename = $this->sanitizeFilename($webform->label())
        . '_' . date('Y-m-d_His')
        . '.zip';

      // Prepare response.
      $response = new BinaryFileResponse($temp_filename);
      $response->headers->set('Content-Type', 'application/zip');
      $response->headers->set('Content-Length', $zip_size);
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );
      $response->headers->set('Cache-Control', 'no-cache, private');
      $response->headers->set('Pragma', 'no-cache');

      // Delete the temp file after it's sent to the client.
      $response->deleteFileAfterSend(TRUE);

      $form_state->setResponse($response);

    } catch (\Exception $e) {
      $this->logger('webform_zip_download')->error('Unexpected error in ZIP download: @error', ['@error' => $e->getMessage()]);
      if ($temp_filename && file_exists($temp_filename)) {
        $this->cleanupTempFile($temp_filename);
      }
      throw $e;
    }
  }

  /**
   * Create a secure temporary file.
   *
   * @return string|false
   *   The path to the temporary file, or FALSE on failure.
   */
  private function createSecureTempFile() {
    $temp_dir = sys_get_temp_dir();
    if (!is_writable($temp_dir)) {
      return FALSE;
    }

    $temp_filename = tempnam($temp_dir, self::TEMP_DIR_PREFIX);
    if ($temp_filename === FALSE) {
      return FALSE;
    }

    // Ensure it has .zip extension for proper handling.
    $zip_filename = $temp_filename . '.zip';
    if (!rename($temp_filename, $zip_filename)) {
      unlink($temp_filename);
      return FALSE;
    }

    return $zip_filename;
  }

  /**
   * Safely delete a temporary file.
   *
   * @param string $filename
   *   The path to the file to delete.
   */
  private function cleanupTempFile($filename) {
    if ($filename && file_exists($filename) && is_writable($filename)) {
      unlink($filename);
    }
  }

  /**
   * Calculate relative path for ZIP entry with validation.
   *
   * @param string $real_path
   *   The real, resolved file path.
   * @param string $form_submission_folder
   *   The submission folder base path.
   * @param string $webform_id
   *   The webform ID.
   *
   * @return string
   *   The relative path for ZIP entry.
   */
  private function getRelativePath($real_path, $form_submission_folder, $webform_id) {
    $relative_part = substr($real_path, strlen($form_submission_folder) + 1);
    return 'webform/' . $webform_id . '/' . $relative_part;
  }

  /**
   * Sanitize filename for safe output.
   *
   * @param string $filename
   *   The filename to sanitize.
   *
   * @return string
   *   The sanitized filename.
   */
  private function sanitizeFilename($filename) {
    // Remove non-alphanumeric characters except spaces, hyphens, underscores.
    $sanitized = preg_replace('/[^a-zA-Z0-9\s_-]/', '', $filename);
    // Replace spaces with underscores.
    $sanitized = preg_replace('/\s+/', '_', $sanitized);
    // Remove multiple consecutive underscores.
    $sanitized = preg_replace('/_+/', '_', $sanitized);
    // Limit length to prevent filesystem issues.
    return substr($sanitized, 0, 100);
  }


  /**
   * Clean up orphaned temporary ZIP files.
   *
   * Removes all temp files with our prefix immediately since they are not needed
   * after download completion.
   */
  private function cleanupOrphanedTempFiles() {
    $temp_dir = sys_get_temp_dir();

    try {
      $files = scandir($temp_dir);
      if ($files === FALSE) {
        return;
      }

      foreach ($files as $file) {
        // Only delete files matching our temp prefix.
        if (strpos($file, self::TEMP_DIR_PREFIX) !== 0) {
          continue;
        }

        $filepath = $temp_dir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($filepath)) {
          continue;
        }

        // Attempt to delete the temp file immediately.
        if (is_writable($filepath)) {
          if (!unlink($filepath)) {
            $this->logger('webform_zip_download')->warning('Failed to delete orphaned temp file: @file', ['@file' => $filepath]);
          }
        }
      }
    } catch (\Exception $e) {
      $this->logger('webform_zip_download')->warning('Error cleaning up orphaned temp files: @error', ['@error' => $e->getMessage()]);
    }
  }

}
