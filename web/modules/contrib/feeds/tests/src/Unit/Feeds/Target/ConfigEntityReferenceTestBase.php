<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\ConfigEntityReference
 * @group feeds
 */
abstract class ConfigEntityReferenceTestBase extends EntityReferenceTestBase {

  /**
   * The transliteration manager.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The manager for managing config schema type plugins.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->transliteration = $this->prophesize(TransliterationInterface::class);
    $this->typedConfigManager = $this->prophesize(TypedConfigManagerInterface::class);
  }

}
