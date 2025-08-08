<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\Stub\DateFormatterStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests DateFormatterStub class.
 */
#[CoversClass(DateFormatterStub::class)]
#[Group('test_helpers')]
#[CoversMethod(DateFormatterStub::class, '__construct')]
#[CoversMethod(DateFormatterStub::class, 'stubSetFormat')]
class DateFormatterStubTest extends UnitTestCase {

  /**
   * Tests the stubSetFormat method of DateFormatterStub.
   */
  public function testStubSetFormat() {
    $dateFormatterStub = TestHelpers::service('date.formatter');
    $this->assertEquals('Sat, 01/01/2000 - 23:00', $dateFormatterStub->format(946728000));
    $dateFormatterStub->stubSetFormat('medium', 'Medium', 'c');
    $this->assertEquals('2000-01-01T23:00:00+11:00', $dateFormatterStub->format(946728000, 'medium'));
    $entity = \Drupal::service('entity_type.manager')->getStorage('date_format')->load('medium');
    $this->assertEquals('Medium', $entity->label());
  }

}
