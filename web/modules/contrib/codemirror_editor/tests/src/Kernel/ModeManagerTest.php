<?php

namespace Drupal\Tests\codemirror_editor\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * A test for plugin.manager.codemirror_mode service.
 *
 * @group codemirror_editor
 */
final class ModeManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['codemirror_editor', 'codemirror_editor_test'];

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
  public function testGetDefinitions() {
    $manager = \Drupal::service('plugin.manager.codemirror_mode');

    $definitions = $manager->getDefinitions();

    self::assertCount(12, $definitions);
    $expected_modes = [
      'clike',
      'css',
      'htmlmixed',
      'javascript',
      'markdown',
      'php',
      'python',
      'ruby',
      'sql',
      'twig',
      'xml',
      'yaml',
    ];
    self::assertEquals($expected_modes, array_keys($definitions));

    // @se codemirror_editor_test_codemirror_mode_info_alter()
    self::assertEquals(['codemirror_editor_test'], $definitions['php']['usage']);
  }

  /**
   * Test callback.
   */
  public function testGetActiveModes() {
    $manager = \Drupal::service('plugin.manager.codemirror_mode');

    $expected_modes = [
      // Required by codemirror_editor_test module.
      'php',
      // Dependency of php mode.
      'clike',
      // Enabled by default.
      'xml',
    ];
    self::assertEquals($expected_modes, $manager->getActiveModes());
  }

  /**
   * Test callback.
   *
   * @dataProvider getData
   */
  public function testNormalizeMode($input, $expected_output) {
    $manager = \Drupal::service('plugin.manager.codemirror_mode');
    $output = $manager->normalizeMode($input);
    self::assertEquals($expected_output, $output);
  }

  /**
   * Data provider for testNormalizeMode().
   *
   * @return array
   *   Mock data set.
   */
  public function getData() {
    return [
      ['text/x-sql', 'text/x-sql'],
      ['PHP', 'text/x-php'],
      ['html', 'text/html'],
      ['missing/mode', 'missing/mode'],
    ];
  }

}
