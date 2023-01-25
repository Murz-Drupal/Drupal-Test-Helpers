<?php

namespace Drupal\Tests\test_helpers\Unit\Stubs;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\StubFactory\EntityStorageStubFactory;
use Drupal\test_helpers\UnitTestHelpers;

/**
 * Tests LanguageManagerStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\LanguageManagerStub
 * @group test_helpers
 */
class EntityStorageStubFactoryTest extends UnitTestCase {

  /**
   * @covers ::__create
   */
  public function testCreate() {
    $entity1 = UnitTestHelpers::createEntityStub(Term::class, [
      'name' => 'Entity 1',
      'parent' => NULL,
    ]);
    $storage = EntityStorageStubFactory::create(
      Term::class,
    );
    $storage->save($entity1);

    $entity2 = UnitTestHelpers::createEntityStub(Term::class, [
      'name' => 'Entity 2',
      'parent' => ['target_id' => 1],
    ]);
    $entity2->save();

    $entity3 = UnitTestHelpers::createEntityStub(Term::class, [
      'name' => 'Entity 3',
      'parent' => ['target_id' => 1],
    ]);
    $storage->save($entity3);

    $result = $storage->loadMultiple();
    $this->assertEquals($entity1->id(), $result[1]->id());
    $this->assertEquals($entity2->id(), $result[2]->id());

    \Drupal::service('entity.query.sql')->stubSetExecuteHandler(function () {
      return $this->condition->conditions()[0]['value'];
    });

    $storageSpecificFuncResult = $storage->loadAllParents($entity3->id());
    end($storageSpecificFuncResult);
    $this->assertEquals($entity1->id(), current($storageSpecificFuncResult)->id());
    reset($storageSpecificFuncResult);
    $this->assertEquals($entity3->id(), current($storageSpecificFuncResult)->id());
    $this->assertArrayNotHasKey(2, $storageSpecificFuncResult);

  }

}
