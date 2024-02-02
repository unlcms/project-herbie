<?php

namespace Drupal\unl_archive_page_import\Form;

use DOMDocument;
use DOMXPath;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;
use Drupal\taxonomy\Entity\Term;

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

    // Process images.
    $imageNodes = $maincontentNode->getElementsByTagName('img');
    foreach ($imageNodes as $imageNode) {
      $src = $imageNode->getAttribute('src');
      $file_name = explode("/", $src);
      $file_name = end($file_name);
      $file_name = explode("?", $file_name);
      $file_name = $file_name[0];

      // Check if image already exists.
      $file = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['filename' => $file_name]);

      if ($file) {
        // Get existing Media entity.
        $fileId = array_shift($file)->id();
        $media = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->loadByProperties(['field_media_image' => $fileId]);
        $media = reset($media);
      }
      else {
        // Download the file and create a new Media entity.
        if (strpos($src, 'http://') === false && strpos($src, 'https://') === false) {
          $src = 'https://unlcms.unl.edu/' . $src;
        }

        $file_data = file_get_contents($src);
        $file = \Drupal::service('file.repository')
          ->writeData($file_data, 'public://media/image/' . $file_name, FileSystemInterface::EXISTS_REPLACE);

        $alt = $imageNode->getAttribute('alt');
        $alt = substr($alt, 0, 500);
        if (empty($alt)) {
          $alt = ' ';
        }

        // Get the ID of the "archive_import" media tag.
        $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $found_terms = $storage->loadByProperties([
          'name' => 'archive_import',
          'vid' => 'tags',
        ]);
        $term = reset($found_terms);
        if (!$term) {
          $term = Term::create([
            'name' => 'archive_import',
            'vid' => 'media_tags',
          ]);
          $term->save();
        }

        $media = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'field_media_image' => [
            'target_id' => $file->id(),
            'alt' => $alt,
          ],
          's_m_tags' => [
            'target_id' => $term->id(),
          ],
        ]);
        $media->setName($file_name)
          ->setPublished(TRUE)
          ->save();
      }

      // Create a new drupal-media DOM element.
      $drupal_media = $dom->createElement('drupal-media');
      $drupal_media->setAttribute('data-entity-type', 'media');
      $drupal_media->setAttribute('data-entity-uuid', $media->uuid());

      // Replace the imported img tag with the new drupal-media element.
      $imageNode->parentNode->replaceChild($drupal_media, $imageNode);
    }

    // Create the Body html source code.
    $body = implode(array_map([$maincontentNode->ownerDocument,"saveHTML"],
      iterator_to_array($maincontentNode->childNodes)));

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
