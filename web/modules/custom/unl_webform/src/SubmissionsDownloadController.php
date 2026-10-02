<?php

namespace Drupal\unl_webform;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\WebformSubmissionExporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SubmissionsDownloadController extends ControllerBase {

  /**
   * The webform submission export service.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new SubmissionsDownloadController.
   *
   * @param \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter
   *   The webform submission exporter service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(WebformSubmissionExporterInterface $submission_exporter, RequestStack $request_stack) {
    $this->submissionExporter = $submission_exporter;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_submission.exporter'),
      $container->get('request_stack')
    );
  }

  /**
   * Generates and downloads the webform submissions export.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A 403 response if the download key is invalid, or a
   *   BinaryFileResponse containing the webform submissions export.
 */
  public function download($webform): Response {
    $key_setting = $this->config('unl_webform.settings')->get('key');
    $request = $this->requestStack->getCurrentRequest();
    $key_param = $request->get('key');

    if (!is_string($key_setting) || !is_string($key_param) || empty($key_setting) || $key_setting !== $key_param) {
      return new Response('Access denied', Response::HTTP_FORBIDDEN);
    }

    $source_entity = NULL;

    $this->submissionExporter->setWebform($webform);
    $this->submissionExporter->setSourceEntity($source_entity);

    $export_options = $this->submissionExporter->getDefaultExportOptions();
    $export_options['access_check'] = FALSE;

    $this->submissionExporter->setExporter($export_options);
    $this->submissionExporter->generate();

    $file_path = $this->submissionExporter->getExportFilePath();

    // Determine if it should be an attachment or inline (defaults to attachment).
    $download = $request->query->has('download') ? $request->query->getBoolean('download') : TRUE;

    $headers = [];
    $response = new BinaryFileResponse($file_path, 200, $headers, FALSE, $download ? 'attachment' : 'inline');
    $response->deleteFileAfterSend(TRUE);

    return $response;
  }

}
