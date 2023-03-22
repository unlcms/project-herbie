<?php

namespace Drupal\feeds\Laminas\Extension\Mediarss;

use Laminas\Feed\Reader\Extension\AbstractEntry;

/**
 * Parses MediaRss data.
 */
class Entry extends AbstractEntry {

  /**
   * Gets the URL from any of the media elements.
   *
   * @param string $type
   *   The media type.
   *
   * @return string|null
   *   Returns the media url.
   */
  protected function getMediaElement($type) {
    $media = NULL;

    $list = $this->xpath->evaluate($this->getXpathPrefix() . '//media:' . $type);
    if ($list->length > 0) {
      $media = $list->item(0);
    }
    return $media;
  }

  /**
   * Gets content from media:content.
   *
   * @return array
   *   A media file.
   */
  public function getMediaContent() {
    $media_key = 'media_content_' . $this->entryKey;
    if (array_key_exists($media_key, $this->data)) {
      return $this->data[$media_key];
    }
    $this->data[$media_key] = NULL;

    $media = $this->getMediaElement('content');

    if (!empty($media) && is_object($media)) {
      $this->data[$media_key] = [
        'url' => $media->getAttribute('url'),
        'width' => $media->getAttribute('width'),
        'height' => $media->getAttribute('height'),
        'description' => '',
      ];

      $media_description = $this->getMediaElement('description');
      if (!empty($media_description) && is_object($media_description)) {
        $this->data[$media_key]['description'] = $media_description->textContent;
      }
    }

    return $this->data[$media_key];
  }

  /**
   * Gets content from media:thumbnail.
   *
   * @return array
   *   A media file.
   */
  public function getMediaThumbnail() {
    $media_key = 'media_thumbnail_' . $this->entryKey;
    if (array_key_exists($media_key, $this->data)) {
      return $this->data[$media_key];
    }
    $this->data[$media_key] = NULL;

    $media = $this->getMediaElement('thumbnail');

    if (!empty($media)) {
      $this->data[$media_key] = [
        'url' => $media->getAttribute('url'),
        'width' => $media->getAttribute('width'),
        'height' => $media->getAttribute('height'),
      ];
    }

    return $this->data[$media_key];
  }

  /**
   * Registers MediaRSS namespaces.
   */
  protected function registerNamespaces() {
    $this->getXpath()
      ->registerNamespace('media', 'http://search.yahoo.com/mrss');
  }

}
