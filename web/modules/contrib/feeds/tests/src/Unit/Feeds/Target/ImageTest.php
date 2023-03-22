<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\feeds\Feeds\Target\Image;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Image
 * @group feeds
 */
class ImageTest extends FileTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'image';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Made-up entity type that we are referencing to.
    $referenceable_entity_type = $this->prophesize(EntityTypeInterface::class);
    $referenceable_entity_type->getKey('label')->willReturn('image label');
    $this->entityTypeManager->getDefinition('file')->willReturn($referenceable_entity_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Image::class;
  }

}
