<?php

namespace Drupal\unl_feeds\Plugin\Tamper;

use DOMDocument;
use DOMXPath;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for processing HTML and replacing img tags with Media items.
 *
 * @Tamper(
 *   id = "unl_feeds_process_body_images",
 *   label = @Translation("Process body images"),
 *   description = @Translation("Looks for img tags in a field and creates Media items for them."),
 *   category = "Other"
 * )
 */
class ProcessBodyImages extends TamperBase {

  const SETTING_SOURCE_SITE = 'website';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_SOURCE_SITE] = 'https://example.com/';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_SOURCE_SITE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source website'),
      '#default_value' => $this->getSetting(self::SETTING_SOURCE_SITE),
      '#description' => $this->t('This address will be prepended to relative img src values in order to download images. Make sure to have a trailing slash on the end.'),
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_SOURCE_SITE => $form_state->getValue(self::SETTING_SOURCE_SITE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    // Get img tags.
    $dom = new DOMDocument();
    @$dom->loadHTML($data, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXpath($dom);

    $nodes = $xpath->query("/");
    if (!$bodyNode = $nodes->item(0)) {
      return false;
    }

    $nodes = $xpath->query("//img");
    foreach ($nodes as $img) {
      // Get image content.
      $src = $img->getAttribute('src');
      $file_name = explode("/", $src);
      $file_name = end($file_name);
      $file_name = explode("?", $file_name);
      $file_name = $file_name[0];

      // Check if image file already exists.
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

        // Alt text.
        $alt = $img->getAttribute('alt');
        $alt = substr($alt, 0, 500);

        if (substr($src, 0, 4) !== 'http'
          && substr($src, 0, 2) !== '//' ) {
          $src = $this->getSetting(self::SETTING_SOURCE_SITE) . $src;
        }
        $file_data = file_get_contents($src);
        $file = \Drupal::service('file.repository')
          ->writeData($file_data, 'public://media/image/' . $file_name, FileSystemInterface::EXISTS_REPLACE);
        $media = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'field_media_image' => [
            'target_id' => $file->id(),
            'alt' => $alt,
          ],
        ]);
        $media->setName($file_name)
          ->setPublished(TRUE)
          ->save();
      }

      // Get the figcaption if available to use as Media caption.
      $figcaption = '';
      $figcaption_nodes = $xpath->query("figcaption//text()", $img->parentNode);
      if ($figcaption_nodes->length) {
        $figcaption = trim($dom->saveHTML($figcaption_nodes->item(0)));
        // Remove the figcaption entirely since it will get added to the Media element.
        $figcaption_nodes->item(0)->parentNode->removeChild($figcaption_nodes->item(0));
      }

      // Create a new drupal-media DOM element.
      $drupal_media = $dom->createElement('drupal-media');
      $drupal_media->setAttribute('data-entity-type', 'media');
      $drupal_media->setAttribute('data-entity-uuid', $media->uuid());
      $drupal_media->setAttribute('data-caption', $figcaption);

      // Replace the <img> with the new <drupal-media> element.
      $img->parentNode->replaceChild($drupal_media, $img);
    }

   return $dom->saveHTML();
  }
}
