<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\feeds\EntityFinderInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FieldTargetDefinition;

/**
 * Base class for entity reference target tests.
 */
abstract class EntityReferenceTestBase extends FieldTargetTestBase {

  /**
   * The entity type manager prophecy used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity storage prophecy used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The Feeds entity finder service.
   *
   * @var \Drupal\feeds\EntityFinderInterface
   */
  protected $entityFinder;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $referencable_entity_type_id = $this->getReferencableEntityTypeId();

    // Entity type manager.
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    // Entity storage (needed for entity queries).
    $this->entityStorage = $this->prophesize($this->getEntityStorageClass());
    $this->entityTypeManager->getStorage($referencable_entity_type_id)->willReturn($this->entityStorage);

    // Made-up entity type that we are referencing to.
    $this->entityTypeManager->getDefinition($referencable_entity_type_id)->willReturn($this->createReferencableEntityType())->shouldBeCalled();

    // Entity finder.
    $this->entityFinder = $this->prophesize(EntityFinderInterface::class);
  }

  /**
   * Returns the entity storage class name to use in this test.
   *
   * @return string
   *   The full name of the entity storage class.
   */
  protected function getEntityStorageClass() {
    return EntityStorageInterface::class;
  }

  /**
   * Returns the entity type machine name to use in this test.
   *
   * @return string
   *   The entity type ID.
   */
  protected function getReferencableEntityTypeId() {
    return 'referenceable_entity_type';
  }

  /**
   * Builds the Drupal service container.
   */
  protected function buildContainer() {
    // EntityReference::prepareTarget() accesses the entity type manager from
    // the global container.
    // @see \Drupal\feeds\Feeds\Target\EntityReference::prepareTarget()
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Creates a Feeds target definition mock.
   *
   * @return \Drupal\feeds\TargetDefinitionInterface
   *   A mocked target definition.
   */
  protected function createTargetDefinitionMock() {
    $referencable_entity_type_id = $this->getReferencableEntityTypeId();

    $method = $this->getMethod($this->getTargetClass(), 'prepareTarget')->getClosure();
    $field_definition_mock = $this->getMockFieldDefinition([
      'target_type' => $referencable_entity_type_id,
      'handler_settings' => ['target_bundles' => []],
    ]);
    $field_definition_mock->expects($this->once())->method('getSetting')->willReturn($referencable_entity_type_id);

    return $method($field_definition_mock);
  }

  /**
   * Creates a referencable entity type instance.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type to use in tests.
   */
  abstract protected function createReferencableEntityType();

  /**
   * @covers ::prepareTarget
   */
  public function testPrepareTarget() {
    $field_definition_mock = $this->getMockFieldDefinition();
    $field_definition_mock->expects($this->once())
      ->method('getSetting')
      ->will($this->returnValue($this->getReferencableEntityTypeId()));

    $method = $this->getMethod($this->getTargetClass(), 'prepareTarget')->getClosure();
    $this->assertInstanceof(FieldTargetDefinition::class, $method($field_definition_mock));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetProperties(): array {
    $field_definition_mock = $this->getMockFieldDefinition();
    $field_definition_mock->expects($this->once())
      ->method('getSetting')
      ->will($this->returnValue($this->getReferencableEntityTypeId()));

    $method = $this->getMethod($this->getTargetClass(), 'prepareTarget')->getClosure();
    return $method($field_definition_mock)
      ->getProperties();
  }

  /**
   * Tests prepareValue() without passing values.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValueEmptyFeed() {
    $method = $this->getProtectedClosure($this->instantiatePlugin(), 'prepareValue');
    $values = ['target_id' => ''];
    $this->expectException(EmptyFeedException::class);
    $method(0, $values);
  }

}
