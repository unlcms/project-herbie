<?php

/**
 * @file
 * Theme callbacks for feeds_ex.
 */

use Drupal\Core\Render\Element;

/**
 * Themes the parser configuration table.
 */
function theme_feeds_ex_configuration_table(array $variables) {
  $element = $variables['element'];

  $rows = [];

  // Render the context at the top. Context is optional.
  if (!empty($element['context'])) {
    $context_row = [];
    foreach (array_keys($element['#header']) as $column) {
      $context_row[] = \Drupal::service('renderer')->render($element['context'][$column]);
    }
    $rows[] = $context_row;
  }

  // Render the sources.
  foreach (Element::children($element['sources']) as $source_key) {
    $source = $element['sources'][$source_key];
    $row = [];
    foreach (array_keys($element['#header']) as $column) {
      $row[] = \Drupal::service('renderer')->render($element['sources'][$source_key][$column]);
    }
    $rows[] = ['data' => $row, 'class' => ['draggable']];
  }

  // Render the add row.
  $add_row = [];
  foreach (array_keys($element['#header']) as $column) {
    $add_row[] = [
      'data' => \Drupal::service('renderer')->render($element['add'][$column]),
      'class' => ['feeds-ex-add-' . $column],
    ];
  }

  $rows[] = ['data' => $add_row, 'class' => ['draggable']];

  $output = _theme('table', [
    'header' => $element['#header'],
    'rows' => $rows,
    'attributes' => ['id' => $element['sources']['#id']],
  ]);
  $output .= \Drupal::service('renderer')->render($element);

  return $output;
}
