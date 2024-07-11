<?php

namespace Drupal\unl_feeds\Plugin\Tamper;

use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Drupal\Component\Utility\Html;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Plugin implementation for creating a Media entity using an image tag.
 *
 * @Tamper(
 *   id = "unl_feeds_create_media",
 *   label = @Translation("Create Media"),
 *   description = @Translation("Provides the capability to integrate custom plugins with the Feeds and Feeds Tamper modules."),
 *   category = "Other"
 * )
 */
class CreateMedia extends TamperBase
{

  /**
   * {@inheritdoc}
   */
  public function tamper($img_tag, TamperableItemInterface $item = NULL)
  {
    if ($img_tag) {

      // Get img tag
      $dom = new \DOMDocument();
      @$dom->loadHTML($img_tag);
      $dom_img_tag_data = $dom->getElementsByTagName('img')->item(0);

      // Get image content
      $img_data = file_get_contents($dom_img_tag_data->getAttribute('src'));
      $file_name = explode("/", $dom_img_tag_data->getAttribute('src'));
      $file_name = end($file_name);

      $file_repo = \Drupal::service('file.repository');
      $file = $file_repo->writeData($img_data, 'public://media/image/' . $file_name, FileSystemInterface::EXISTS_REPLACE);

      // Check if image has an alt attribute
      if ($dom_img_tag_data->hasAttribute('alt')) {
        $alt_text = $dom_img_tag_data->getAttribute('alt');
        if (empty($alt_text)) {
          $alt_text = null;
        }
      } else {
        $alt_text = null;
      }

      //Add a 'add-img-alt-text' image tag to an image if alt text is empty/null
      if (is_null($alt_text)) {
        $vocabulary_machine_name = 'media_tags';
        $term_name = 'add-img-alt-text';

        $vocabulary = Vocabulary::load($vocabulary_machine_name);
        $add_img_alt_text_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' =>  $term_name, 'vid' => $vocabulary->id()]);

        //check if add-img-alt-text term exists first
        if ($add_img_alt_text_term) {
          $term_object = reset($add_img_alt_text_term);
          $term_id = $term_object->id();
        } else {
          $term = Term::create([
            'name' => $term_name,
            'vid' => $vocabulary_machine_name,
          ]);
          $term->save();
          $term_id = $term->id();
        }
      }

      $media = Media::create([
        'bundle' => 'image',
        'uid' => \Drupal::currentUser()->id(),
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' =>  $alt_text,
        ],
      ]);

      $media->setName($file_name)
        ->setPublished(TRUE)
        ->save();

      if ($term_id) {
        $media->s_m_tags->target_id = $term_id;
        $media->save();
      }

      return $media->id();

    } else {
      return false;
    }
  }
}
