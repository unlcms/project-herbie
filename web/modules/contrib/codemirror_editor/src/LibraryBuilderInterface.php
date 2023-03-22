<?php

namespace Drupal\codemirror_editor;

/**
 * CodeMirror library builder interface.
 */
interface LibraryBuilderInterface {

  const CODEMIRROR_VERSION = '5.51.0';

  const CDN_URL = 'https://cdn.jsdelivr.net/npm/codemirror@{version}';

  const LIBRARY_PATH = '/libraries/codemirror/';

  /**
   * Builds a definition for CodeMirror library.
   *
   * @return array
   *   CodeMirror library definition.
   */
  public function build();

}
