<?php

namespace Drupal\Tests\codemirror_editor\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * A test for codemirror_editor_library_info_build().
 *
 * @group codemirror_editor
 */
final class LibraryBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['codemirror_editor', 'codemirror_editor_test'];

  /**
   * CodeMirror library definition when CDN option is 'Off'.
   *
   * @var array
   */
  protected $localFixture = [
    'remote' => 'https://codemirror.net',
    'version' => '5.51.0',
    'license' => [
      'name' => 'MIT',
      'url' => 'http://codemirror.net/LICENSE',
      'gpl-compatible' => TRUE,
    ],
    'js' => [
      '/libraries/codemirror/lib/codemirror.js' => [],
      '/libraries/codemirror/mode/xml/xml.js' => [],
      '/libraries/codemirror/mode/clike/clike.js' => [],
      '/libraries/codemirror/mode/php/php.js' => [],
      '/libraries/codemirror/mode/css/css.js' => [],
      '/libraries/codemirror/addon/fold/foldcode.js' => [],
      '/libraries/codemirror/addon/fold/foldgutter.js' => [],
      '/libraries/codemirror/addon/fold/brace-fold.js' => [],
      '/libraries/codemirror/addon/fold/xml-fold.js' => [],
      '/libraries/codemirror/addon/fold/comment-fold.js' => [],
      '/libraries/codemirror/addon/display/autorefresh.js' => [],
      '/libraries/codemirror/addon/display/fullscreen.js' => [],
      '/libraries/codemirror/addon/display/placeholder.js' => [],
      '/libraries/codemirror/addon/mode/overlay.js' => [],
      '/libraries/codemirror/addon/edit/closetag.js' => [],
      '/libraries/codemirror/addon/comment/comment.js' => [],
      '/libraries/codemirror/addon/selection/active-line.js' => [],
    ],
    'css' => [
      'component' => [
        '/libraries/codemirror/lib/codemirror.css' => [],
        '/libraries/codemirror/addon/fold/foldgutter.css' => [],
        '/libraries/codemirror/addon/display/fullscreen.css' => [],
      ],
    ],
  ];

  /**
   * CodeMirror library definition when CDN option is 'On'.
   *
   * @var array
   */
  protected $remoteFixture = [
    'remote' => 'https://codemirror.net',
    'version' => '5.51.0',
    'license' => [
      'name' => 'MIT',
      'url' => 'http://codemirror.net/LICENSE',
      'gpl-compatible' => TRUE,
    ],
    'js' => [
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/lib/codemirror.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/mode/xml/xml.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/mode/clike/clike.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/mode/php/php.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/mode/css/css.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/fold/foldcode.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/fold/foldgutter.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/fold/brace-fold.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/fold/xml-fold.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/fold/comment-fold.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/display/autorefresh.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/display/fullscreen.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/display/placeholder.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/mode/overlay.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/edit/closetag.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/comment/comment.js' => ['type' => 'external'],
      'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/selection/active-line.js' => ['type' => 'external'],
    ],
    'css' => [
      'component' => [
        'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/lib/codemirror.css' => ['type' => 'external'],
        'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/fold/foldgutter.css' => ['type' => 'external'],
        'https://cdn.jsdelivr.net/npm/codemirror@5.51.0/addon/display/fullscreen.css' => ['type' => 'external'],
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['codemirror_editor']);
  }

  /**
   * Test callback.
   */
  public function testLibraryBuild() {

    $config = \Drupal::configFactory()
      ->getEditable('codemirror_editor.settings');

    // Remote minified.
    $expected_build = [
      'codemirror' => self::minify($this->remoteFixture),
    ];
    self::assertEquals($expected_build, codemirror_editor_library_info_build());

    // Remote non-minified.
    $settings = [
      'cdn' => TRUE,
      'minified' => FALSE,
      'theme' => 'default',
      'language_modes' => ['xml'],
    ];
    $config->setData($settings)->save();

    $expected_build = [
      'codemirror' => $this->remoteFixture,
    ];
    self::assertEquals($expected_build, codemirror_editor_library_info_build());

    // Local non-minified.
    $settings = [
      'cdn' => FALSE,
      'minified' => FALSE,
      'theme' => 'default',
      'language_modes' => ['xml'],
    ];
    $config->setData($settings)->save();

    $expected_build = [
      'codemirror' => $this->localFixture,
    ];
    self::assertEquals($expected_build, codemirror_editor_library_info_build());

    // Local minified.
    $settings = [
      'cdn' => FALSE,
      'minified' => TRUE,
      'theme' => 'default',
      'language_modes' => ['xml'],
    ];
    $config->setData($settings)->save();

    $expected_build = [
      'codemirror' => self::minify($this->localFixture),
    ];
    self::assertEquals($expected_build, codemirror_editor_library_info_build());

    // Local non-minified with Yaml mode and Cobalt theme.
    $settings = [
      'cdn' => FALSE,
      'minified' => FALSE,
      'theme' => 'cobalt',
      'language_modes' => ['xml', 'yaml'],
    ];
    $config->setData($settings)->save();

    $expected_build = [
      'codemirror' => $this->localFixture,
    ];
    $expected_build['codemirror']['js']['/libraries/codemirror/mode/yaml/yaml.js'] = [];
    $expected_build['codemirror']['css']['theme']['/libraries/codemirror/theme/cobalt.css'] = [];
    self::assertEquals($expected_build, codemirror_editor_library_info_build());
  }

  /**
   * Minifies file names in library definition.
   *
   * @param array $library_definition
   *   The library definition.
   *
   * @return array
   *   Processed library definition.
   */
  protected static function minify(array $library_definition) {

    foreach ($library_definition['js'] as $file_name => $options) {
      unset($library_definition['js'][$file_name]);
      $file_name = preg_replace('#\.js$#', '.min.js', $file_name);
      $options['minified'] = TRUE;
      $library_definition['js'][$file_name] = $options;
    }

    foreach ($library_definition['css']['component'] as $file_name => $options) {
      unset($library_definition['css']['component'][$file_name]);
      $file_name = preg_replace('#\.css#', '.min.css', $file_name);
      $options['minified'] = TRUE;
      $library_definition['css']['component'][$file_name] = $options;
    }

    return $library_definition;
  }

}
