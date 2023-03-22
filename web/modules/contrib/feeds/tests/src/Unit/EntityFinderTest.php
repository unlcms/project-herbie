<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\feeds\EntityFinder;

/**
 * @coversDefaultClass \Drupal\feeds\EntityFinder
 * @group feeds
 */
class EntityFinderTest extends FeedsUnitTestCase {

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
   * Entity repository used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Entity type manager.
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    // Entity storage (needed for entity queries).
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager->getStorage('foo')->willReturn($this->entityStorage);

    // Entity repository.
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::class);
  }

  /**
   * Creates an entity finder instance.
   *
   * @return \Drupal\feeds\EntityFinder
   *   The entity finder instance to test with.
   */
  protected function createEntityFinderInstance() {
    return new EntityFinder($this->entityTypeManager->reveal(), $this->entityRepository->reveal());
  }

  /**
   * @covers ::findEntities
   */
  public function testFindEntities() {
    // Entity query.
    $entity_query = $this->prophesize(QueryInterface::class);
    $entity_query->accessCheck(FALSE)->willReturn($entity_query);
    $entity_query->range(0, 1)->willReturn($entity_query);
    $entity_query->condition('field_ref', 1)->willReturn($entity_query);
    $entity_query->execute()->willReturn([12]);
    $this->entityStorage->getQuery()->willReturn($entity_query)->shouldBeCalled();

    $entity_ids = $this->createEntityFinderInstance()->findEntities('foo', 'field_ref', 1);
    $this->assertEquals([12], $entity_ids);
  }

  /**
   * @covers ::findEntities
   */
  public function testFindEntitiesNotFound() {
    // Entity query.
    $entity_query = $this->prophesize(QueryInterface::class);
    $entity_query->accessCheck(FALSE)->willReturn($entity_query);
    $entity_query->range(0, 1)->willReturn($entity_query);
    $entity_query->condition('field_ref', 1)->willReturn($entity_query);
    $entity_query->execute()->willReturn([]);
    $this->entityStorage->getQuery()->willReturn($entity_query)->shouldBeCalled();

    $entity_ids = $this->createEntityFinderInstance()->findEntities('foo', 'field_ref', 1);
    $this->assertEquals([], $entity_ids);
  }

  /**
   * @covers ::findEntities
   */
  public function testFindMultipleEntities() {
    // Entity query.
    $entity_query = $this->prophesize(QueryInterface::class);
    $entity_query->accessCheck(FALSE)->willReturn($entity_query);
    $entity_query->range(0, 1)->shouldNotBeCalled();
    $entity_query->condition('field_ref', 1)->willReturn($entity_query);
    $entity_query->execute()->willReturn([12, 13, 14]);
    $this->entityStorage->getQuery()->willReturn($entity_query)->shouldBeCalled();

    $entity_ids = $this->createEntityFinderInstance()->findEntities('foo', 'field_ref', 1, [], TRUE);
    $this->assertEquals([12, 13, 14], $entity_ids);
  }

  /**
   * @covers ::findEntities
   * @covers ::getBundleKey
   */
  public function testFindEntitiesWithBundleRestriction() {
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getKey('bundle')->willReturn('type')->shouldBeCalled();
    $this->entityTypeManager->getDefinition('foo')->willReturn($entity_type->reveal())->shouldBeCalled();

    // Entity query.
    $entity_query = $this->prophesize(QueryInterface::class);
    $entity_query->accessCheck(FALSE)->willReturn($entity_query);
    $entity_query->condition('type', ['qux'], 'IN')->willReturn($entity_query);
    $entity_query->range(0, 1)->willReturn($entity_query);
    $entity_query->condition('field_ref', 1)->willReturn($entity_query);
    $entity_query->execute()->willReturn([16]);
    $this->entityStorage->getQuery()->willReturn($entity_query)->shouldBeCalled();

    $entity_ids = $this->createEntityFinderInstance()->findEntities('foo', 'field_ref', 1, [
      'qux',
    ]);
    $this->assertEquals([16], $entity_ids);
  }

  /**
   * @covers ::findEntities
   */
  public function testFindEntitiesByUuid() {
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->id()->willReturn(17);
    $this->entityRepository->loadEntityByUuid('foo', '31835cb0-7302-403b-92ab-c228547d13fc')
      ->willReturn($entity);

    $entity_ids = $this->createEntityFinderInstance()->findEntities('foo', 'uuid', '31835cb0-7302-403b-92ab-c228547d13fc');
    $this->assertEquals([17], $entity_ids);
  }

  /**
   * @covers ::findEntities
   */
  public function testFindEntitiesByUuidNotFound() {
    $this->entityRepository->loadEntityByUuid('foo', '31835cb0-7302-403b-92ab-c228547d13fc')
      ->willReturn(NULL);

    $entity_ids = $this->createEntityFinderInstance()->findEntities('foo', 'uuid', '31835cb0-7302-403b-92ab-c228547d13fc');
    $this->assertEquals([], $entity_ids);
  }

}
