<?php

namespace Drupal\Tests\field_css\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for Field CSS tests.
 */
abstract class TestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to access CSS fields.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $codeUser;

  /**
   * A user without permission to access CSS fields.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $authUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_css',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'page']);

    // Create a CSS Code field and attach to page content type.
    FieldStorageConfig::create([
      'field_name' => 'field_code',
      'entity_type' => 'node',
      'type' => 'css',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_code',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'CSS Code',
    ])->save();

    $entity_display_repository = \Drupal::service('entity_display.repository');

    $entity_display_repository->getFormDisplay('node', 'page', 'default')
      ->setComponent('field_code', [
        'type' => 'css',
      ])
      ->save();

    $entity_display_repository->getViewDisplay('node', 'page', 'default')
      ->setComponent('field_code', [
        'type' => 'css',
      ])
      ->save();

    // Create user with 'access css fields' permission.
    $this->codeUser = $this->drupalCreateUser([
      'access css fields',
      'create page content',
      'edit any page content',
    ]);

    // Create user without 'access css fields' permission.
    $this->authUser = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
    ]);

  }

  /**
   * Helper function to find NodeElement for a widget/formatter summary cell.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   NodeElement for the formatter summary cell.
   */
  protected function getSummaryCell() {
    return $this->getSession()->getPage()->find('xpath', '//tr[@id="field-code"]//td[@class="field-plugin-summary-cell"]');
  }

}
