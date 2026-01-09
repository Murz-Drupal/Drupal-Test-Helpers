<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\Stub;

use Drupal\Component\EventDispatcher\Event;
use Drupal\test_helpers\Stub\ContainerAwareEventDispatcherStub;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests ContainerAwareEventDispatcherStub class.
 *
 * @covers ContainerAwareEventDispatcherStub::__construct
 * @covers ContainerAwareEventDispatcherStub::dispatch
 * @covers ContainerAwareEventDispatcherStub::stubGetDispatchedEvents
 */
#[CoversClass(ContainerAwareEventDispatcherStub::class)]
#[Group('test_helpers')]
class ContainerAwareEventDispatcherStubTest extends UnitTestCase {

  /**
   * Tests the stubGetDispatchedEvents method of ContainerAwareEventDispatcher.
   */
  public function testStubGetDispatchedEvents() {
    if (version_compare(\Drupal::VERSION, '10.0', '<')) {
      $this->markTestSkipped('This test is skipped for Drupal versions lower than 10.0.');
    }

    $service = TestHelpers::service('event_dispatcher');
    $event = new MyCustomEvent($param = 'my_param');
    $service->dispatch($event);
    $service->dispatch($event);
    if (version_compare(\Drupal::VERSION, '10.0', '>=')) {
      $service->dispatch($event, $name = 'my_custom_name');
    }

    $events = $service->stubGetDispatchedEvents();
    $this->assertCount(2, $events[MyCustomEvent::class]);
    $this->assertEquals($param, $events[MyCustomEvent::class][0]->myParam);
    if (version_compare(\Drupal::VERSION, '10.0', '>=')) {
      $this->assertCount(2, $events);
      $this->assertCount(1, $events[$name]);
      $this->assertEquals($param, $events[$name][0]->myParam);
    }
    else {
      $this->assertCount(1, $events);
    }
  }

}

/**
 * A helper class to test the event dispatcher stub.
 */
#[CoversClass(ContainerAwareEventDispatcherStub::class)]
#[Group('test_helpers')]
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
