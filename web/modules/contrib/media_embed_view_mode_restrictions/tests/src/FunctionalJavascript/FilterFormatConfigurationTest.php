<?php

namespace Drupal\Tests\media_embed_view_mode_restrictions\FunctionalJavascript;

use Drupal\filter\Entity\FilterFormat;

/**
 * Tests configuration storage, installation, & uninstallation.
 *
 * @group media_embed_view_mode_restrictions
 */
class FilterFormatConfigurationTest extends MediaEmbedViewModeRestrictionsTestBase {

  /**
   * Test configuration form and storage.
   *
   * Also tests install and uninstall.
   */
  public function testConfiguration() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();
    $session->resizeWindow(1200, 2000);

    // Set filter format configuration before enabling Media Embed View Mode
    // Restrictions module.
    $this->drupalGet('admin/config/content/formats/manage/media_embed_test');

    $page->checkField('filters[media_embed][status]');
    $page->fillField('filters[media_embed][settings][default_view_mode]', 'viewmode1');
    $page->checkField('filters[media_embed][settings][allowed_view_modes][viewmode1]');
    $page->checkField('filters[media_embed][settings][allowed_view_modes][viewmode2]');
    $page->checkField('filters[media_embed][settings][allowed_view_modes][viewmode3]');
    $page->pressButton('Save configuration');

    // Install Media Embed View Mode Restrictions module.
    \Drupal::service('module_installer')->install(['media_embed_view_mode_restrictions']);

    // Verify configuration is unchanged upon installation.
    $filter_format = FilterFormat::load('media_embed_test');
    /** @var \Drupal\filter\Plugin\FilterInterface */
    $media_embed_filter = $filter_format->filters('media_embed');

    $this->assertSame($media_embed_filter->settings['default_view_mode'], 'viewmode1');
    $this->assertEmpty($media_embed_filter->settings['allowed_media_types']);
    $allowed_view_modes = [
      'viewmode1' => 'viewmode1',
      'viewmode2' => 'viewmode2',
      'viewmode3' => 'viewmode3',
    ];
    $this->assertSame($media_embed_filter->settings['allowed_view_modes'], $allowed_view_modes);

    // Set per-bundle configuration and verify #states behavior.
    $this->drupalGet('admin/config/content/formats/manage/media_embed_test');

    $assert_session->checkboxNotChecked('filters[media_embed][settings][allowed_media_types][file]');
    $assert_session->checkboxNotChecked('filters[media_embed][settings][allowed_media_types][image_bundle_1]');
    $assert_session->checkboxNotChecked('filters[media_embed][settings][allowed_media_types][image_bundle_2]');

    $assert_session->assertVisibleInViewport('xpath', "//*[@id='edit-filters-media-embed-settings-bundle-view-modes-file']/summary");
    $assert_session->assertVisibleInViewport('xpath', "//*[@id='edit-filters-media-embed-settings-bundle-view-modes-image-bundle-1']/summary");
    $assert_session->assertVisibleInViewport('xpath', "//*[@id='edit-filters-media-embed-settings-bundle-view-modes-image-bundle-2']/summary");

    // Only permit Image Bundle 1 and Image Bundle 2.
    $page->checkField('filters[media_embed][settings][allowed_media_types][image_bundle_1]');
    $page->checkField('filters[media_embed][settings][allowed_media_types][image_bundle_2]');

    // Verify #states behavior.
    $element = $page->findById('edit-filters-media-embed-settings-bundle-view-modes-file');
    $this->assertFalse($element->isVisible());
    $element = $page->findById('edit-filters-media-embed-settings-bundle-view-modes-image-bundle-1');
    $this->assertTrue($element->isVisible());
    $element = $page->findById('edit-filters-media-embed-settings-bundle-view-modes-image-bundle-2');
    $this->assertTrue($element->isVisible());

    // Configure Image Bundle 1.
    $element = $page->find('xpath', '//*[@id="edit-filters-media-embed-settings-bundle-view-modes-image-bundle-1"]/summary');
    $element->click();
    $page->fillField('filters[media_embed][settings][bundle_view_modes][image_bundle_1][default_view_mode]', 'viewmode2');
    $page->checkField('filters[media_embed][settings][bundle_view_modes][image_bundle_1][allowed_view_modes][viewmode1]');
    $page->checkField('filters[media_embed][settings][bundle_view_modes][image_bundle_1][allowed_view_modes][viewmode2]');

    // Configure Image Bundle 2.
    $element = $page->find('xpath', '//*[@id="edit-filters-media-embed-settings-bundle-view-modes-image-bundle-2"]/summary');
    $element->click();
    $page->fillField('filters[media_embed][settings][bundle_view_modes][image_bundle_2][default_view_mode]', 'viewmode3');
    $page->checkField('filters[media_embed][settings][bundle_view_modes][image_bundle_2][allowed_view_modes][viewmode3]');

    // Verify File configuration is not visible.
    $element = $page->findById('edit-filters-media-embed-settings-bundle-view-modes-file');
    $this->assertFalse($element->isVisible());

    $page->pressButton('Save configuration');

    // Verify configuration storage.
    $filter_format = FilterFormat::load('media_embed_test');
    /** @var \Drupal\filter\Plugin\FilterInterface */
    $media_embed_filter = $filter_format->filters('media_embed');

    // Verify disused 'default_view_mode' config is unchanged.
    $this->assertSame($media_embed_filter->settings['default_view_mode'], 'viewmode1');
    // Verify used 'allowed_media_types ' config is changed.
    $allowed_media_types = [
      'image_bundle_1' => 'image_bundle_1',
      'image_bundle_2' => 'image_bundle_2',
    ];
    $this->assertSame($media_embed_filter->settings['allowed_media_types'], $allowed_media_types);
    // Verify disused 'allowed_view_modes' config is unchanged.
    $allowed_view_modes = [
      'viewmode1' => 'viewmode1',
      'viewmode2' => 'viewmode2',
      'viewmode3' => 'viewmode3',
    ];
    $this->assertSame($media_embed_filter->settings['allowed_view_modes'], $allowed_view_modes);

    // Verify no configuration is stored for File media bundle.
    $this->assertFalse(isset($media_embed_filter->settings['bundle_view_modes']['file']));

    // Verify configuration storage for Image Bundle 1 media bundle.
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_1']['default_view_mode'], 'viewmode2');
    $allowed_view_modes = [
      'viewmode1' => 'viewmode1',
      'viewmode2' => 'viewmode2',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_1']['allowed_view_modes'], $allowed_view_modes);

    // Verify configuration storage for Image Bundle 2 media bundle.
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_2']['default_view_mode'], 'viewmode3');
    $allowed_view_modes = [
      'viewmode3' => 'viewmode3',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_2']['allowed_view_modes'], $allowed_view_modes);

    // Add File media bundle configuration.
    $this->drupalGet('admin/config/content/formats/manage/media_embed_test');

    $page->checkField('filters[media_embed][settings][allowed_media_types][file]');

    $element = $page->find('xpath', '//*[@id="edit-filters-media-embed-settings-bundle-view-modes-file"]/summary');
    $element->click();
    $page->fillField('filters[media_embed][settings][bundle_view_modes][file][default_view_mode]', 'default');
    $page->checkField('filters[media_embed][settings][bundle_view_modes][file][allowed_view_modes][default]');

    $page->pressButton('Save configuration');

    // Verify file bundle configuration has been added.
    $filter_format = FilterFormat::load('media_embed_test');
    /** @var \Drupal\filter\Plugin\FilterInterface */
    $media_embed_filter = $filter_format->filters('media_embed');

    // Verify configuration storage for File media bundle.
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['file']['default_view_mode'], 'default');
    $allowed_view_modes = [
      'default' => 'default',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['file']['allowed_view_modes'], $allowed_view_modes);

    // Uncheck all allowed bundle checkboxes.
    $this->drupalGet('admin/config/content/formats/manage/media_embed_test');

    $page->uncheckField('filters[media_embed][settings][allowed_media_types][file]');
    $page->uncheckField('filters[media_embed][settings][allowed_media_types][image_bundle_1]');
    $page->uncheckField('filters[media_embed][settings][allowed_media_types][image_bundle_2]');

    $page->pressButton('Save configuration');

    // Verify configuration storage.
    // If no allowed bundles are specified, then all are allowed.
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['file']['default_view_mode'], 'default');
    $allowed_view_modes = [
      'default' => 'default',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['file']['allowed_view_modes'], $allowed_view_modes);

    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_1']['default_view_mode'], 'viewmode2');
    $allowed_view_modes = [
      'viewmode1' => 'viewmode1',
      'viewmode2' => 'viewmode2',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_1']['allowed_view_modes'], $allowed_view_modes);

    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_2']['default_view_mode'], 'viewmode3');
    $allowed_view_modes = [
      'viewmode3' => 'viewmode3',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_2']['allowed_view_modes'], $allowed_view_modes);

    // Disallow File media bundle.
    // This should result in its configuration being removed.
    $this->drupalGet('admin/config/content/formats/manage/media_embed_test');

    $page->checkField('filters[media_embed][settings][allowed_media_types][image_bundle_1]');
    $page->checkField('filters[media_embed][settings][allowed_media_types][image_bundle_2]');
    $assert_session->checkboxNotChecked('filters[media_embed][settings][allowed_media_types][file]');

    $page->pressButton('Save configuration');

    // Verify configuration storage.
    $filter_format = FilterFormat::load('media_embed_test');
    /** @var \Drupal\filter\Plugin\FilterInterface */
    $media_embed_filter = $filter_format->filters('media_embed');

    // Verify File media bundle configuration has been removed.
    $this->assertFalse(isset($media_embed_filter->settings['bundle_view_modes']['file']));

    // Verify Image Bundle 1 stored configuration is unchanged.
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_1']['default_view_mode'], 'viewmode2');
    $allowed_view_modes = [
      'viewmode1' => 'viewmode1',
      'viewmode2' => 'viewmode2',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_1']['allowed_view_modes'], $allowed_view_modes);

    // Verify Image Bundle 2 stored configuration is unchanged.
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_2']['default_view_mode'], 'viewmode3');
    $allowed_view_modes = [
      'viewmode3' => 'viewmode3',
    ];
    $this->assertSame($media_embed_filter->settings['bundle_view_modes']['image_bundle_2']['allowed_view_modes'], $allowed_view_modes);

    // Uninstall Media Embed View Mode Restrictions module.
    \Drupal::service('module_installer')->uninstall(['media_embed_view_mode_restrictions'], FALSE);

    // Verify configuration storage.
    $filter_format = FilterFormat::load('media_embed_test');
    /** @var \Drupal\filter\Plugin\FilterInterface */
    $media_embed_filter = $filter_format->filters('media_embed');

    // Verify original 'default_view_mode' remains unchanged.
    $this->assertSame($media_embed_filter->settings['default_view_mode'], 'viewmode1');
    // Verify changed 'allowed_media_types' is unaffected by uninstallation.
    $allowed_media_types = [
      'image_bundle_1' => 'image_bundle_1',
      'image_bundle_2' => 'image_bundle_2',
    ];
    $this->assertSame($media_embed_filter->settings['allowed_media_types'], $allowed_media_types);
    // Verify original 'allowed_view_modes' remains unchanged.
    $allowed_view_modes = [
      'viewmode1' => 'viewmode1',
      'viewmode2' => 'viewmode2',
      'viewmode3' => 'viewmode3',
    ];
    $this->assertSame($media_embed_filter->settings['allowed_view_modes'], $allowed_view_modes);

    // Verify per-bundle configuration was removed during uninstallation.
    $this->assertFalse(isset($media_embed_filter->settings['bundle_view_modes']['file']));
    $this->assertFalse(isset($media_embed_filter->settings['bundle_view_modes']['image_bundle_1']));
    $this->assertFalse(isset($media_embed_filter->settings['bundle_view_modes']['image_bundle_2']));
  }

}
