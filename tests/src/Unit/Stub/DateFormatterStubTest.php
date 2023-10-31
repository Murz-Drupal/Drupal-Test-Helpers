<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests DateFormatterStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\DateFormatterStub
 * @group test_helpers
 */
class DateFormatterStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::stubSetFormat
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
