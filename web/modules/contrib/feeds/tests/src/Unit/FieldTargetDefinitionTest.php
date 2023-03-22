<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\feeds\FieldTargetDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * @coversDefaultClass \Drupal\feeds\FieldTargetDefinition
 * @group feeds
 */
class FieldTargetDefinitionTest extends FeedsUnitTestCase {

  /**
   * A prophesized data definition for the field item.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\TypedData\ComplexDataDefinitionInterface
   */
  protected $itemDefinition;

  /**
   * A prophesized data definition for the field property.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $propertyDefinition;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->itemDefinition = $this->prophesize(ComplexDataDefinitionInterface::class);
    $this->propertyDefinition = $this->prophesize(DataDefinitionInterface::class);
  }

  /**
   * Creates a prophesized field definition.
   *
   * @return \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Field\FieldDefinitionInterface
   *   A prophesized field definition.
   */
  protected function createFieldDefinition() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getItemDefinition()
      ->willReturn($this->itemDefinition->reveal());

    return $field_definition;
  }

  /**
   * Tests that the label is taken from field definition.
   *
   * @covers ::getPropertyLabel
   */
  public function testGetPropertyLabel() {
    $this->propertyDefinition->getLabel()
      ->willReturn('Foo label')
      ->shouldBeCalled();

    $this->itemDefinition->getPropertyDefinition('foo')
      ->willReturn($this->propertyDefinition->reveal())
      ->shouldBeCalled();

    $field_definition = $this->createFieldDefinition();

    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition->reveal());
    $this->assertEquals('Foo label', $target_definition->getPropertyLabel('foo'));
  }

  /**
   * Tests that a custom set property label takes precedence.
   *
   * @covers ::getPropertyLabel
   */
  public function testGetPropertyLabelWithCustomSetLabel() {
    $this->propertyDefinition->getLabel()
      ->willReturn('Foo label');

    $this->itemDefinition->getPropertyDefinition('foo')
      ->willReturn($this->propertyDefinition->reveal());

    $field_definition = $this->createFieldDefinition();
    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition->reveal());

    $target_definition->addProperty('foo', 'Custom label');

    $this->assertEquals('Custom label', $target_definition->getPropertyLabel('foo'));
  }

  /**
   * Tests no errors when the label for a custom property isn't set.
   *
   * @covers ::getPropertyLabel
   */
  public function testGetPropertyLabelOfNonExistingProperty() {
    $field_definition = $this->createFieldDefinition();
    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition->reveal());

    $target_definition->addProperty('bar');
    $this->assertEquals('', $target_definition->getPropertyLabel('bar'));
  }

  /**
   * Tests that the description is taken from field definition.
   *
   * @covers ::getPropertyDescription
   */
  public function testGetPropertyDescription() {
    $this->propertyDefinition->getDescription()
      ->willReturn('Foo description')
      ->shouldBeCalled();

    $this->itemDefinition->getPropertyDefinition('foo')
      ->willReturn($this->propertyDefinition->reveal())
      ->shouldBeCalled();

    $field_definition = $this->createFieldDefinition();

    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition->reveal());
    $this->assertEquals('Foo description', $target_definition->getPropertyDescription('foo'));
  }

  /**
   * Tests that a custom set property description takes precedence.
   *
   * @covers ::getPropertyDescription
   */
  public function testGetPropertyDescriptionWithCustomSetDescription() {
    $this->propertyDefinition->getDescription()
      ->willReturn('Foo description');

    $this->itemDefinition->getPropertyDefinition('foo')
      ->willReturn($this->propertyDefinition->reveal());

    $field_definition = $this->createFieldDefinition();
    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition->reveal());

    $target_definition->addProperty('foo', 'Custom label', 'Custom description');

    $this->assertEquals('Custom description', $target_definition->getPropertyDescription('foo'));
  }

  /**
   * Tests no errors when the description for a custom property isn't set.
   *
   * @covers ::getPropertyDescription
   */
  public function testGetPropertyDescriptionOfNonExistingProperty() {
    $field_definition = $this->createFieldDefinition();
    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition->reveal());

    $target_definition->addProperty('bar');
    $this->assertEquals('', $target_definition->getPropertyDescription('bar'));
  }

}
