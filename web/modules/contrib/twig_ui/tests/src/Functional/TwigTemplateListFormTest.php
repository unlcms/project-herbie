<?php

namespace Drupal\Tests\twig_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\twig_ui\Entity\TwigTemplate;

/**
 * Test the template list form.
 *
 * @group twig_ui
 */
class TwigTemplateListFormTest extends BrowserTestBase {

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test non-administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'twig_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setup() : void {
    parent::setup();

    \Drupal::service('theme_installer')->install(['grant']);
    \Drupal::service('theme_installer')->install(['perkins']);

    // Create an admin user.
    $this->adminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer twig templates',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);
  }

  /**
   * Test route permissions.
   */
  public function testPermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/admin/structure/templates');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/templates');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Test TwigTemplateForm.
   *
   * @covers \Drupal\twig_ui\Form\TwigTemplateListForm
   */
  public function testForm() {
    $page = $this->getSession()->getPage();

    $this->generateTemplate('node', 'Node', 'node');
    $this->generateTemplate('node_a', 'Node A', 'node__a');
    $this->generateTemplate('node_b', 'Node B', 'node__b');
    $this->generateTemplate('node_c', 'Node C', 'node__c');
    $this->generateTemplate('node_d', 'Node D', 'node__d');
    $this->generateTemplate('node_e', 'Node E', 'node__e');
    $this->generateTemplate('node_f', 'Node F', 'node__f');
    $this->generateTemplate('node_g', 'Node G', 'node__g');
    $this->generateTemplate('node_h', 'Node H', 'node__h');
    $this->generateTemplate('node_i', 'Node I', 'node__i');
    $this->generateTemplate('node_j', 'Node J', 'node__j');
    $this->generateTemplate('node_k', 'Node K', 'node__k');
    $this->generateTemplate('node_l', 'Node L', 'node__l');
    $this->generateTemplate('node_m', 'Node M', 'node__m');
    $this->generateTemplate('node_n', 'Node N', 'node__n');
    $this->generateTemplate('node_o', 'Node O', 'node__o');
    $this->generateTemplate('node_p', 'Node P', 'node__p');
    $this->generateTemplate('node_q', 'Node Q', 'node__q');
    $this->generateTemplate('node_r', 'Node R', 'node__r');
    $this->generateTemplate('node_s', 'Node S', 'node__s');
    $this->generateTemplate('node_t', 'Node T', 'node__t');
    $this->generateTemplate('node_u', 'Node U', 'node__u');
    $this->generateTemplate('node_v', 'Node V', 'node__v');
    $this->generateTemplate('node_w', 'Node W', 'node__w');
    $this->generateTemplate('node_x', 'Node X', 'node__x');
    $this->generateTemplate('node_y', 'Node Y', 'node__y');
    $this->generateTemplate('node_z', 'Node Z', 'node__z');
    $this->generateTemplate('node_aa', 'Node AA', 'node__aa', NULL, [
      'grant',
      'perkins',
    ]);
    $this->generateTemplate('node_ab', 'Node AB', 'node__ab', NULL, [
      'grant',
      'perkins',
    ]);
    $this->generateTemplate('node_ac', 'Node AC', 'node__ac', NULL, ['grant'], TRUE);
    $this->generateTemplate('node_ad', 'Node AD', 'node__ad', NULL, ['grant'], TRUE);
    $this->generateTemplate('node_ae', 'Node AE', 'node__ae', NULL, ['grant'], TRUE);
    $this->generateTemplate('node_af', 'Node AF', 'node__af', NULL, ['grant'], TRUE);
    $this->generateTemplate('node_abc', 'Node ABC', 'node__abc');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/templates');

    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(25, $rows, '25 table rows found.');
    $element = $page->find('xpath', '//nav[contains(@class, "pager")]//li[contains(@class, "pager__item--last")]/a');
    $this->assertNotEmpty($element, 'Pager is rendered.');

    $page->find('xpath', '//nav[contains(@class, "pager")]//li[contains(@class, "pager__item--last")]/a')
      ->click();
    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(9, $rows, '9 table rows found.');

    $page->fillField('template_label', 'a');
    $page->pressButton('Apply');
    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(8, $rows, '8 table rows found.');

    $page->fillField('template_label', '');
    $page->fillField('template_id', 'node_a');
    $page->pressButton('Apply');
    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(8, $rows, '8 table rows found.');

    $page->fillField('template_id', '');
    $page->fillField('theme_suggestion', 'node__ab');
    $page->pressButton('Apply');
    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(2, $rows, '2 table row found.');

    $page->fillField('theme_suggestion', '');
    $page->fillField('theme', 'perkins');
    $page->pressButton('Apply');
    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(2, $rows, '2 table rows found.');

    $page->fillField('theme', '_none');
    $page->fillField('status', 'enabled');
    $page->pressButton('Apply');
    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(4, $rows, '4 table rows found.');

    $page->pressButton('Reset');
    $rows = $this->xpath('//form[@id="twig-ui-template-list-form"]//table/tbody/tr');
    $this->assertCount(25, $rows, '25 table rows found.');
    $element = $page->find('xpath', '//nav[contains(@class, "pager")]//li[contains(@class, "pager__item--last")]/a');
    $this->assertNotEmpty($element, 'Pager is rendered.');
  }

  /**
   * Helper methods to generate Twig UI templates.
   *
   * @param string $id
   *   The machine name.
   * @param string $label
   *   The human-readable name.
   * @param string $theme_suggestion
   *   The theme suggestion.
   * @param string $template_code
   *   The template Twig code.
   * @param array $themes
   *   An array of theme machine names.
   * @param bool $status
   *   Whether or not the template is enabled.
   *   FALSE by default to reduce writes to file system and cache clearing
   *   during testing.
   */
  protected function generateTemplate($id, $label, $theme_suggestion, $template_code = "{{ content }}", array $themes = ['grant'], $status = FALSE) {
    TwigTemplate::create([
      'id' => $id,
      'label' => $label,
      'theme_suggestion' => $theme_suggestion,
      'template_code' => $template_code,
      'themes' => $themes,
      'status' => $status,
    ])->save();
  }

}
