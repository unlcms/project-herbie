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
    return 'unl_archive_page_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<p>This tool imports pages from a sunsetting Drupal 7 site into this site. <a target="_blank" href="https://cms-docs.unl.edu/developers/transition-pages/">Instructions available at cms-docs.unl.edu</a>.</p>';
    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL of the site being imported from.'),
      '#required' => TRUE,
      '#description' => $this->t('Example: https://example.unl.edu/'),
    ];
    $form['sitemap'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option #1: Path to XML sitemap of pages to import.'),
      '#required' => FALSE,
      '#description' => $this->t('Example: https://example.unl.edu/sitemap.xml'),
    ];
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option #2: Single URL of a page to import.'),
      '#required' => FALSE,
      '#description' => $this->t('Example: https://example.unl.edu/sample-page'),
    ];
    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => 'Start the import',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $base_url = $form_state->getValue('base_url');
    $base_url = trim($base_url, '/') . '/';
    $base_url = str_replace('http://', 'https://', $base_url);

    $batch = [
      'title' => t('Importing pages'),
      'operations' => [],
      'init_message' => t('Import process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.'),
    ];

    $sitemap = $form_state->getValue('sitemap');
    if ($sitemap) {
      $request = \Drupal::httpClient()->get($sitemap);
      $body = $request->getBody();
      $site_map = simplexml_load_string($body);

      foreach ($site_map->url as $item) {
        $url = (string) $item->loc;
        $alias = substr($url, strlen($base_url) - 1);
        $batch['operations'][] = [
          ['\Drupal\unl_archive_page_import\Form\ImportForm', 'importPage'],
          [$url, $alias, $base_url]
        ];
      }
    }
    elseif ($url = $form_state->getValue('url')) {
      $alias = substr($url, strlen($base_url) - 1);
      $batch['operations'][] = [
        ['\Drupal\unl_archive_page_import\Form\ImportForm', 'importPage'],
        [$url, $alias, $base_url]
      ];
    }
    else {
      // @TODO Display a message to the user that they need to enter something.
      return;
    }

    batch_set($batch);
    \Drupal::messenger()->addMessage('Success!');

    $form_state->setRebuild(TRUE);
  }

  /**
   * @param $entity
   * Deletes an entity
   */
  public static function importPage($url, $alias, $base_url, &$context) {
    \Drupal::logger('unl_archive_page_import')->info('Starting import of ' . $url);
    $media_added = [];

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
//    $base_tags = $dom->getElementsByTagName('base');
//    $page_base = NULL;
//    if ($base_tags->length > 0) {
//      $page_base = $base_tags->item(0)->getAttribute('href');
//    }

    // Page title.
    $title = $url;
    $nodes = $xpath->query("//header[@id='dcf-page-title']/h1//text()");
    if ($nodes->length > 0) {
      $title = $dom->saveHTML($nodes->item(0));
    }

    // Get the Main Content html for the Body field.
    $set_title_hidden = false;
    $nodes = $xpath->query("//div[contains(@class, 'dcf-hero-default')]");
    $x = $nodes->item(0);
    if ($nodes->item(0)) {
      // Default hero is present, so only get content area.
      $nodes = $xpath->query("//div[contains(@class, 'dcf-main-content')]");
      if (!$maincontentNode = $nodes->item(0)) {
        return FALSE;
      }
    }
    else {
      // Hero photo is present, so get entire .dcf-main.
      $nodes = $xpath->query("//main[@id='dcf-main']");
      if (!$maincontentNode = $nodes->item(0)) {
        return FALSE;
      }
      // Set the checkbox field to hide the default page title.
      $set_title_hidden = true;
    }


    /**
     * Process images.
     */
    $imageNodes = $maincontentNode->getElementsByTagName('img');
    foreach ($imageNodes as $imageNode) {
      $src = $imageNode->getAttribute('src');
      $file_name = explode("/", $src);
      $file_name = end($file_name);
      $file_name = explode("?", $file_name);
      $file_name = $file_name[0];

      // Check if image is local or external and skip if the later.
      $base_url_http = str_replace('https://', 'http://', $base_url);
      if (strpos($src, 'http://') === false && strpos($src, 'https://') === false) {
        // Local file.
        $src = $base_url . $src;
      }
      elseif (strpos($src, $base_url) === false && strpos($src, $base_url_http) === false) {
        // This is a URL to an external site so skip touching it.
        continue;
      }

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
        if (!$media) {
          $media = \Drupal::entityTypeManager()
            ->getStorage('media')
            ->loadByProperties(['field_media_file' => $fileId]);
        }
        $media = reset($media);
      }
      else {
        // Download the file and create a new Media entity.
        $file_data = file_get_contents($src);
        $destination = 'public://media/image';
        \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
        $file = \Drupal::service('file.repository')
          ->writeData($file_data, 'public://media/image/' . $file_name, FileSystemInterface::EXISTS_REPLACE);

        $alt = $imageNode->getAttribute('alt');
        $alt = substr($alt, 0, 500);

        // Get the ID of the "archive_import" media tag (or create it) that all imported files will be assigned.
        $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $found_terms = $storage->loadByProperties([
          'name' => 'archive_import',
          'vid' => 'media_tags',
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

      // Get the URL of the media item.
      if ($media->field_media_image) {
        $media_src = $media->field_media_image->entity->getFileUri();
      }
      else {
        $media_src = $media->field_media_file->entity->getFileUri();
      }
      $media_src = \Drupal::service('file_url_generator')->generateString($media_src);

      // Replace the imported img tag src with the path to the new media item.
      $imageNode->setAttribute('src', $media_src);
      $media_added[] = $media;
    }




    /**
     * Process links to files.
     */
    $linkNodes = $maincontentNode->getElementsByTagName('a');
    foreach ($linkNodes as $a) {
      $href = $a->getAttribute('href');
      $file_name = explode("/", $href);
      $file_name = end($file_name);
      $file_name = explode("?", $file_name);
      $file_name = $file_name[0];

      // Check if link is local or external and skip if the later.
      $base_url_http = str_replace('https://', 'http://', $base_url);
      if (strpos($href, 'http://') === false && strpos($href, 'https://') === false) {
        // Local link.
        $href = $base_url . $href;
      }
      elseif (strpos($href, $base_url) === false && strpos($href, $base_url_http) === false) {
        // This is a URL to an external site so skip touching it.
        continue;
      }

      $response = \Drupal::httpClient()->head($href, ['http_errors' => false]);
      $content_type = $response->getHeader('Content-Type');
      if (strpos($content_type[0], 'html') !== false) {
        // Link to an HTML page, skip it.
        continue;
      }

      // Check if file already exists.
      $file = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['filename' => $file_name]);

      if ($file) {
        // Get existing Media entity.
        $fileId = array_shift($file)->id();
        $media = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->loadByProperties(['field_media_file' => $fileId]);
        if (!$media) {
          $media = \Drupal::entityTypeManager()
            ->getStorage('media')
            ->loadByProperties(['field_media_image' => $fileId]);
        }
        $media = reset($media);
      }
      else {
        // Download the file and create a new Media entity.
        $file_data = file_get_contents($href);
        $destination = 'public://media/file';
        \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
        $file = \Drupal::service('file.repository')
          ->writeData($file_data, 'public://media/file/' . $file_name, FileSystemInterface::EXISTS_REPLACE);

        // Get the ID of the "archive_import" media tag (or create it) that all imported files will be assigned.
        $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $found_terms = $storage->loadByProperties([
          'name' => 'archive_import',
          'vid' => 'media_tags',
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
          'bundle' => 'file',
          'uid' => \Drupal::currentUser()->id(),
          'field_media_file' => [
            'target_id' => $file->id(),
          ],
          's_m_tags' => [
            'target_id' => $term->id(),
          ],
        ]);
        $media->setName($file_name)
          ->setPublished(TRUE)
          ->save();
      }

      // Get the URL of the media item.
      if ($media->field_media_file) {
        $media_src = $media->field_media_file->entity->getFileUri();
      }
      else {
        $media_src = $media->field_media_image->entity->getFileUri();
      }
      $media_src = \Drupal::service('file_url_generator')->generateString($media_src);

      // Replace the imported link href with the path to the new media item.
      $a->setAttribute('href', $media_src);
      $media_added[] = $media;
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
    if ($set_title_hidden) {
      $entity->set('s_n_page_options', [['value' => 'title_hidden']]);
    }
    $entity->save();

    foreach ($media_added as $media) {
      \Drupal::service('entity_usage.usage')->registerUsage($media->id(), $media->getEntityTypeId(), $entity->id(), $entity->getEntityTypeId(), 'en', 1, 'entity_reference', 'archive_page_body');
    }

    $context['results'][] = $url;
    $context['message'] = t('Imported @title', array('@title' => $url));
    \Drupal::logger('unl_archive_page_import')->info('Finished import of ' . $url);
  }

}
