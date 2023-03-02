<?php

namespace Drupal\field_css\Traits;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Component\Utility\Html;
use Drupal\field\Entity\FieldConfig;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;

/**
 * Utility methods.
 */
trait CssTrait {

  /**
   * Return any prefixes to be added to selectors for an entity and view mode.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being rendered.
   * @param string $view_mode
   *   The view mode being rendered.
   *
   * @return array
   *   The prefixes to be added to the item.
   */
  public static function itemPrefixes(ContentEntityInterface $entity, string $view_mode) {
    $return = [];

    $fields = $entity->getFieldDefinitions();
    foreach ($fields as $field) {
      if ($field instanceof FieldConfig) {
        if ($field->getType() == 'css') {
          $formatter_settings = EntityViewDisplay::load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $view_mode)
            ->get('content')[$field->getName()]['settings'];

          if ($formatter_settings['prefix'] == 'entity-item') {
            $return[] = CssTrait::generatePrefix($entity);
          }
          elseif ($formatter_settings['prefix'] == 'fixed-value') {
            $return[] = $formatter_settings['fixed_prefix_value'];
          }
        }
      }
    }
    return $return;
  }

  /**
   * Generates a scoped prefix.
   *
   * Pattern: 'scoped-css--[entity-type]-[entity-id]'.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being rendered.
   * @param bool $leading_period
   *   Whether or not the prefix should be returned with a leading period.
   *
   * @return string
   *   A scoped prefix.
   */
  public static function generatePrefix(ContentEntityInterface $entity, $leading_period = FALSE) {
    $prefix = Html::cleanCssIdentifier('scoped-css--' . $entity->getEntityTypeId() . '-' . $entity->id());
    return ($leading_period) ? '.' . $prefix : $prefix;
  }

  /**
   * Add a prefix to all selectors.
   *
   * @param string $css_code
   *   The CSS code block to be processed.
   * @param string $prefix
   *   The selector to be prefixed to all selectors.
   *
   * @return string
   *   The CSS code block with selectors prefixed.
   */
  public static function addSelectorPrefix($css_code, $prefix) {
    $parser = new Parser($css_code);
    $css_document = $parser->parse();
    foreach ($css_document->getAllDeclarationBlocks() as $block) {
      foreach ($block->getSelectors() as $selector) {
        // Loop over all selector parts (the comma-separated strings in a
        // selector) and prepend the id.
        $selector->setSelector($prefix . ' ' . $selector->getSelector());
      }
    }
    return $css_document->render(OutputFormat::createPretty());
  }

  /**
   * Formats CSS.
   *
   * This method should be used in instances where CSS code is not run
   * through CssTrait::addSelectorPrefix().
   *
   * @param string $css_code
   *   The CSS code block to be processed.
   *
   * @return string
   *   The formatted CSS code.
   */
  public static function formatCss($css_code) {
    $parser = new Parser($css_code);
    $css_document = $parser->parse();
    return $css_document->render(OutputFormat::createPretty());
  }

}
