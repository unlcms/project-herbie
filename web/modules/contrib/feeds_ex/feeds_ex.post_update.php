<?php

/**
 * @file
 * Post update functions for Feeds Extensible Parsers.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds_ex\Feeds\Parser\XmlParser;
use Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser;
use Drupal\feeds_ex\Feeds\Parser\JsonParserBase;

/**
 * Add types to existing custom sources on feeds_ex parsers.
 */
function feeds_ex_post_update_custom_sources(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'feeds_feed_type', function (FeedTypeInterface $feed_type) {
      $parser = $feed_type->getParser();
      if ($parser instanceof QueryPathXmlParser) {
        $custom_source_type = 'querypathxml';
      }
      elseif ($parser instanceof XmlParser) {
        $custom_source_type = 'xml';
      }
      elseif ($parser instanceof JsonParserBase) {
        $custom_source_type = 'json';
      }
      else {
        return FALSE;
      }

      // Add type to custom sources to those that don't have it yet.
      foreach ($feed_type->getCustomSources() as $name => $custom_source) {
        if (empty($custom_source['type'])) {
          $custom_source['type'] = $custom_source_type;
          $feed_type->addCustomSource($name, $custom_source);
        }
      }

      // Remove "sources" and "debug_mode" configuration.
      $config = $parser->getConfiguration();
      unset($config['sources']);
      unset($config['debug_mode']);
      $parser->setConfiguration($config);

      return TRUE;
    });
}
