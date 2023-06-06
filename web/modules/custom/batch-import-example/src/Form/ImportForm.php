<?php

namespace Drupal\batch_import_example\Form;

use DOMDocument;
use DOMXPath;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;

/**
 * Provides a form for deleting a batch_import_example entity.
 *
 * @ingroup batch_import_example
 */
class ImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'batch_import_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#prefix'] = '<p>This example form will import 3 pages from the docs/animals.json example</p>';

    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => 'Proceed',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $base_url = 'https://unlcms.unl.edu/';
    $base_url = trim($base_url, '/') . '/';

    $url = 'https://unlcms.unl.edu/sitemap.xml';
    $request = \Drupal::httpClient()->get($url);
    $body = $request->getBody();
    $site_map = simplexml_load_string($body);

    $batch = [
      'title' => t('Importing animals'),
      'operations' => [],
      'init_message' => t('Import process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.'),
    ];

    foreach ($site_map->url as $item) {
      $url = (string)$item->loc;
      $alias = substr($url, strlen($base_url)-1);
      $batch['operations'][] = [
        ['\Drupal\batch_import_example\Form\ImportForm', 'importPage'], [$url, $alias]
      ];
    }

    batch_set($batch);
    \Drupal::messenger()->addMessage('Imported ' . count($site_map) . ' animals!');

    $form_state->setRebuild(TRUE);
  }

  /**
   * @param $entity
   * Deletes an entity
   */
  public static function importPage($url, $alias, &$context) {
    $request = \Drupal::httpClient()->get($url);
    $body = $request->getBody();
    if (!$body) {
      $context['message'] = t('The page at ' . $url . ' is empty. Ignoring.');
      return false;
    }

    $dom = new DOMDocument();
    if (!@$dom->loadHTML($body)) {
      return false;
    }
    $xpath = new DOMXpath($dom);

    // Check to see if there's a base tag on this page.
    $base_tags = $dom->getElementsByTagName('base');
    $page_base = NULL;
    if ($base_tags->length > 0) {
      $page_base = $base_tags->item(0)->getAttribute('href');
    }

    // Page title.
    $title = $url;
    $nodes = $xpath->query("//header[@id='dcf-page-title']/h1//text()");
    if ($nodes->length > 0) {
      $title = $dom->saveHTML($nodes->item(0));
    }

    // Get the Main Content html for the Body field.
    $nodes = $xpath->query("//div[contains(@class, 'dcf-main-content')]");
    if (!$maincontentNode = $nodes->item(0)) {
      return false;
    }
    $body = implode(array_map([$maincontentNode->ownerDocument,"saveHTML"],
      iterator_to_array($maincontentNode->childNodes)));

    // Process images.
    $imageNodes = $maincontentNode->getElementsByTagName('img');
    foreach ($imageNodes as $imageNode) {
      $src = $imageNode->getAttribute('src');
    }

    // Create a node.
    $entity = Node::create([
        'type' => 'archive_page',
        'title' => $title,
        'archive_page_body' => [['value' => $body, 'format' => 'archive']],
        'path' => [
          'alias' => $alias,
          'pathauto' => PathautoState::SKIP,
        ],
      ]
    );
    $entity->save();

    $context['results'][] = $url;
    $context['message'] = t('Created @title', array('@title' => $url));
  }

}
