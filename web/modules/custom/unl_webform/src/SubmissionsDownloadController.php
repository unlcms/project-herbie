<?php

namespace Drupal\unl_webform;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SubmissionsDownloadController extends ControllerBase {

  /**
   * The webform submission export service.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->submissionExporter = $container->get('webform_submission.exporter');
    return $instance;
  }

  public function download($webform) {
    $key_setting = \Drupal::config('unl_webform.settings')->get('key');
    $key_param = \Drupal::request()->get('key');

    if (is_string($key_setting) && is_string($key_param)
      && !empty($key_setting) && $key_setting === $key_param) {

      user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['view any webform submission']);

      $source_entity = NULL;

      $submission_exporter = $this->submissionExporter;
      $submission_exporter->setWebform($webform);
      $submission_exporter->setSourceEntity($source_entity);

      $export_options = $this->submissionExporter->getDefaultExportOptions();
      $this->submissionExporter->setExporter($export_options);
      $this->submissionExporter->generate();

      $file_path = $this->submissionExporter->getExportFilePath();
      $headers = [];
      $response = new BinaryFileResponse($file_path, 200, $headers, FALSE, $download ? 'attachment' : 'inline');
      $response->deleteFileAfterSend(TRUE);

      user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['view any webform submission']);

      return $response;
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
