<?php

namespace Drupal\Tests\dcf_classes\Functional;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of dcf_classes module.
 *
 * @group dcf_classes
 */
class DcfClassesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dcf_classes',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create non-admin user.
    $this->authUser = $this->drupalCreateUser([
      'access administration pages',
    ]);

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer dcf classes',
    ]);
  }

  /**
   * Tests the DCF Classes config form.
   */
  public function testDcfClassesForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->authUser);
    $this->drupalGet('/admin/config/content/dcf/classes');
    // Needed until 8.8 per https://www.drupal.org/project/drupal/issues/2985690
    $assert_session->pageTextContains('You are not authorized to access this page.');
    try {
      $assert_session->statusCodeEquals(403);
    }
    catch (UnsupportedDriverActionException $e) {
      // Ignore the exception if status codes are not available.
    }

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/dcf/classes');
    $assert_session->pageTextContains('DCF Classes');

    $test_value = 'dcf-regular' . PHP_EOL . 'dcf-capitalize';
    $page->fillField('heading', $test_value);

    $test_value = 'dcf-bleed' . PHP_EOL . 'dcf-wrapper';
    $page->fillField('section', $test_value);

    $test_value = 'Cream|dcf-bleed dcf-wrapper unl-bg-cream dcf-pt-5 dcf-pb-5' . PHP_EOL . 'Scarlet|dcf-bleed dcf-wrapper unl-bg-scarlet dcf-inverse dcf-pt-5 dcf-pb-5';
    $page->fillField('section_packages', $test_value);

    $test_value = 'column-class-1' . PHP_EOL . 'column-class-2' . PHP_EOL . 'column-class-3' . PHP_EOL . 'column-class-4';
    $page->fillField('column', $test_value);

    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');

    // Retrieve config to verify.
    $config = \Drupal::service('config.factory')->getEditable('dcf_classes.classes');

    $test_value = ['dcf-regular', 'dcf-capitalize'];
    $stored_value = $config->get('heading');
    $this->assertIdentical($test_value, $stored_value);

    $test_value = ['dcf-bleed', 'dcf-wrapper'];
    $stored_value = $config->get('section');
    $this->assertIdentical($test_value, $stored_value);

    $test_value = [
      'Cream' => 'dcf-bleed dcf-wrapper unl-bg-cream dcf-pt-5 dcf-pb-5',
      'Scarlet' => 'dcf-bleed dcf-wrapper unl-bg-scarlet dcf-inverse dcf-pt-5 dcf-pb-5',
    ];
    $stored_value = $config->get('section_packages');
    $this->assertIdentical($test_value, $stored_value);

    $test_value = [
      'column-class-1',
      'column-class-2',
      'column-class-3',
      'column-class-4',
    ];
    $stored_value = $config->get('column');
    $this->assertIdentical($test_value, $stored_value);
  }

}
