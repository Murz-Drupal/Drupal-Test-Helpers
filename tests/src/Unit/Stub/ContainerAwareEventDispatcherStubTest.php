<?php

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Component\EventDispatcher\Event;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigFactoryStub class.
 *
 * @coversDefaultClass \Drupal\test_helpers\Stub\ContainerAwareEventDispatcherStub
 * @group test_helpers
 */
class ContainerAwareEventDispatcherStubTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::dispatch
   * @covers ::stubGetDispatchedEvents
   */
  public function testStubGetDispatchedEvents() {
    $service = TestHelpers::service('event_dispatcher');
    $event = new MyCustomEvent($param = 'my_param');
    $service->dispatch($event);
    $service->dispatch($event);
    $service->dispatch($event, $name = 'my_custom_name');

    $events = $service->stubGetDispatchedEvents();
    $this->assertCount(2, $events);
    $this->assertCount(2, $events[MyCustomEvent::class]);
    $this->assertCount(1, $events[$name]);
    $this->assertEquals($param, $events[$name][0]->myParam);
  }

}

/**
 * A helper class to test the event dispatcher stub.
 */
class MyCustomEvent extends Event {

  /**
   * A public myParam property.
   *
   * @var string
   */
  public string $myParam;

  public function __construct(string $myParam) {
    $this->myParam = $myParam;
  }

}
