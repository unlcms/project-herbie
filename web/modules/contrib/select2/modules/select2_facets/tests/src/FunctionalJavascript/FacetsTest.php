<?php

namespace Drupal\Tests\select2_facets\FunctionalJavascript;

use Drupal\Component\Serialization\Json;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\facets\Entity\Facet;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the select2 element.
 *
 * @group select2
 */
class FacetsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['select2_facets_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $reference1 = EntityTestMulRevPub::create(['name' => 'Reference 1']);
    $reference1->save();
    $reference2 = EntityTestMulRevPub::create(['name' => 'Reference 2']);
    $reference2->save();
    $reference3 = EntityTestMulRevPub::create(['name' => 'Reference 3']);
    $reference3->save();
    EntityTestMulRevPub::create([
      'name' => 'Entity 1',
      'field_references' => [$reference1, $reference2],
    ])->save();
    EntityTestMulRevPub::create([
      'name' => 'Entity 2',
      'field_references' => [$reference1, $reference3],
    ])->save();

    $account = $this->createUser(['view test entity']);
    $this->drupalLogin($account);

    // Index all entities.
    search_api_cron();

    $this->drupalPlaceBlock('facet_block:referenced');
  }

  /**
   * Tests basic select2 functionality.
   *
   * @dataProvider providerTestBasicFunctionality
   */
  public function testBasicFunctionality(array $config, array $expected_settings): void {

    $facet = Facet::load('referenced');
    $facet->setWidget('select2', $config);
    $facet->save();

    $this->drupalGet('/test-entity-view');

    $settings = $this->getSession()->getPage()->findField('Referenced[]')->getAttribute('data-select2-config');
    foreach ($expected_settings as $key => $value) {
      if ($key === 'ajax') {
        $this->assertArrayHasKey($key, Json::decode($settings));
      }
      else {
        $this->assertSame(Json::decode($settings)[$key], $value);
      }
    }

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->click('.form-item-referenced .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('Reference');
    $this->assertNotEmpty($assert_session->waitForElement('xpath', '//li[@class="select2-results__option" and text()="Reference 2"]'));
    $page->find('xpath', '//li[@class="select2-results__option" and text()="Reference 2"]')->click();

    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('f%5B0%5D=referenced%3A2', $current_url);

    $this->click('.form-item-referenced .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('Reference');
    $this->assertNotEmpty($assert_session->waitForElement('xpath', '//li[@class="select2-results__option" and text()="Reference 1"]'));
    $page->find('xpath', '//li[@class="select2-results__option" and text()="Reference 1"]')->click();

    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('f%5B0%5D=referenced%3A1&f%5B1%5D=referenced%3A2', $current_url);
  }

  /**
   * Data provider for testBasicFunctionality().
   *
   * @return array
   *   The data.
   */
  public function providerTestBasicFunctionality(): array {
    return [
      [[], ['tags' => FALSE]],
      [['autocomplete' => TRUE], ['ajax' => [], 'tags' => FALSE]],
    ];
  }

}
