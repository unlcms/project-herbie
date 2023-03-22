<?php

namespace Drupal\Tests\dcf_lazyload\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\image\Functional\ImageFieldTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests functionality of DCF Lazy Loading module.
 *
 * @group dcf_lazyload
 */
class DcfLazyLoadTest extends ImageFieldTestBase {

  use TestFileCreationTrait;
  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'node',
    'responsive_image',
    'dcf_lazyload',
    'dcf_lazyload_test',
    'views',
  ];

  /**
   * Machine name of image field.
   *
   * @var string
   */
  protected $imageFieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create test responsive image style.
    $this->responsiveImgStyle = ResponsiveImageStyle::create([
      'id' => 'test_responsive_image_style',
      'label' => 'Test Responsive Image Style',
      'breakpoint_group' => 'responsive_image',
      'fallback_image_style' => 'medium',
    ]);
    $this->responsiveImgStyle->addImageStyleMapping('responsive_image.viewport_sizing', '1x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => '(min-width: 700px) 700px, 100vw',
        'sizes_image_styles' => [
          'medium' => 'medium',
          'large' => 'large',
        ],
      ],
    ])->save();

    $this->imageFieldName = 'field_image';
    $this->createImageField($this->imageFieldName, 'article', ['uri_scheme' => 'public']);
  }

  /**
   * Tests formatter settings and markup rendering.
   */
  public function testResponsiveFieldFormatter() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Create image object from fixture image file.
    // Use 1x1 ratio test image.
    \Drupal::service('extension.list.module')->getPath('dcf_lazyload');
    $test_image = new \stdClass();
    $test_image->uri = $module_path . '/tests/fixtures/test_image_1x1.jpg';

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $this->imageFieldName, 'article', $alt);
    $node_storage->resetCache([$nid]);

    // Update display formatter to use responsive image style.
    $display_options = [
      'type' => 'responsive_image',
      'settings' => [
        'image_link' => '',
        'responsive_image_style' => 'test_responsive_image_style',
      ],
    ];
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display = $display_repository->getViewDisplay('node', 'article');
    $display->setComponent($this->imageFieldName, $display_options)
      ->save();

    $this->drupalGet('node/' . $nid);

    // Verify lazy loading is not enabled.
    $this->assertEmpty($this
      ->cssSelect('.dcf-lazy-load'));
    $this->assertNoRaw('loading="lazy"');
    $this->assertNoRaw('data-src');
    $this->assertNoRaw('data-srcset');
    $this->assertNoRaw('<noscript>');
    $this->assertNoRaw('dcf-ratio');
    $this->assertNoRaw('dcf-ratio-1x1');
    // Verify height and width attributes are not set.
    $this->assertNoRaw('height="640"');
    $this->assertNoRaw('width="640"');

    // Update display options to enable DCF Lazy Loading.
    $display_options['third_party_settings'] = [
      'dcf_lazyload' => [
        'dcf_lazyload_enable' => TRUE,
      ],
    ];
    $display->setComponent($this->imageFieldName, $display_options)->save();

    $this->drupalGet('node/' . $nid);

    // Verify lazy loading is enabled.
    $this->assertNotEmpty($this
      ->cssSelect('.dcf-lazy-load'));
    $this->assertRaw('loading="lazy"');
    $this->assertRaw('data-src');
    $this->assertRaw('data-srcset');
    $this->assertRaw('<noscript>');
    $this->assertRaw('dcf-ratio');
    // Verify ratio class is added to wrapper.
    $this->assertRaw('dcf-ratio-1x1');
    // Verify height and width attributes are set.
    $this->assertRaw('height="640"');
    $this->assertRaw('width="640"');

    // Upload an image with a 4x3 ratio.
    \Drupal::service('extension.list.module')->getPath('dcf_lazyload');
    $test_image = new \stdClass();
    $test_image->uri = $module_path . '/tests/fixtures/test_image_4x3.jpg';

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $this->imageFieldName, 'article', $alt);
    $node_storage->resetCache([$nid]);

    $this->drupalGet('node/' . $nid);

    $this->assertRaw('dcf-ratio-4x3');

    // Upload an image with a 3x4 ratio.
    \Drupal::service('extension.list.module')->getPath('dcf_lazyload');
    $test_image = new \stdClass();
    $test_image->uri = $module_path . '/tests/fixtures/test_image_3x4.jpg';

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $this->imageFieldName, 'article', $alt);
    $node_storage->resetCache([$nid]);

    $this->drupalGet('node/' . $nid);

    $this->assertRaw('dcf-ratio-3x4');

    // Upload an image with a 16x9 ratio.
    \Drupal::service('extension.list.module')->getPath('dcf_lazyload');
    $test_image = new \stdClass();
    $test_image->uri = $module_path . '/tests/fixtures/test_image_16x9.jpg';

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $this->imageFieldName, 'article', $alt);
    $node_storage->resetCache([$nid]);

    $this->drupalGet('node/' . $nid);

    $this->assertRaw('dcf-ratio-16x9');

    // Upload an image with a 9x16 ratio.
    \Drupal::service('extension.list.module')->getPath('dcf_lazyload');
    $test_image = new \stdClass();
    $test_image->uri = $module_path . '/tests/fixtures/test_image_9x16.jpg';

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $this->imageFieldName, 'article', $alt);
    $node_storage->resetCache([$nid]);

    $this->drupalGet('node/' . $nid);

    $this->assertRaw('dcf-ratio-9x16');

    // Upload an image with a non-standard ratio to a new article node.
    \Drupal::service('extension.list.module')->getPath('dcf_lazyload');
    $test_image = new \stdClass();
    $test_image->uri = $module_path . '/tests/fixtures/test_image_non_standard_ratio.jpg';

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $this->imageFieldName, 'article', $alt);
    $node_storage->resetCache([$nid]);

    $this->drupalGet('node/' . $nid);

    // Verify inline CSS is added for non-standard ratio image.
    $this->assertRaw('{ padding-top: 93.07%!important; }');
  }

  /**
   * Tests views field formatter settings and markup rendering.
   */
  public function testResponsiveViewField() {
    $config_path = \Drupal::service('extension.list.module')->getPath('dcf_lazyload_test') . '/config/optional';
    $config_source = new FileStorage($config_path);
    \Drupal::service('config.installer')->installOptionalConfig($config_source);
    drupal_flush_all_caches();

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Create image object from fixture image file.
    // Use 1x1 ratio test image.
    \Drupal::service('extension.list.module')->getPath('dcf_lazyload');
    $test_image = new \stdClass();
    $test_image->uri = $module_path . '/tests/fixtures/test_image_1x1.jpg';

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $this->imageFieldName, 'article', $alt);
    $node_storage->resetCache([$nid]);

    // Load view with DCF lazyloading disabled.
    $this->drupalGet('dcf-lazyload-test-view');

    // Verify lazy loading is not enabled.
    $this->assertEmpty($this
      ->cssSelect('.dcf-lazy-load'));
    $this->assertNoRaw('loading="lazy"');
    $this->assertNoRaw('data-src');
    $this->assertNoRaw('data-srcset');
    $this->assertNoRaw('<noscript>');
    $this->assertNoRaw('dcf-ratio');
    $this->assertNoRaw('dcf-ratio-1x1');
    // Verify height and width attributes are set.
    $this->assertNoRaw('height="640"');
    $this->assertNoRaw('width="640"');

    // Load view with DCF lazyloading enabled.
    $this->drupalGet('dcf-lazyload-test-view-enabled');

    // Verify lazy loading is enabled.
    $this->assertNotEmpty($this
      ->cssSelect('.dcf-lazy-load'));
    $this->assertRaw('loading="lazy"');
    $this->assertRaw('data-src');
    $this->assertRaw('data-srcset');
    $this->assertRaw('<noscript>');
    $this->assertRaw('dcf-ratio');
    // Verify ratio class is added to wrapper.
    $this->assertRaw('dcf-ratio-1x1');
    // Verify height and width attributes are set.
    $this->assertRaw('height="640"');
    $this->assertRaw('width="640"');
  }

}
