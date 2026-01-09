<?php

declare(strict_types=1);

namespace Drupal\Tests\test_helpers\Unit\TestHelpersApi;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests CreateEntityStub API function.
 *
 * @covers TestHelpers::callEventSubscriber
 */
#[CoversClass(TestHelpers::class)]
#[Group('test_helpers')]
class CallEventSubscriberTest extends UnitTestCase {

  /**
   * Tests the callEventSubscriber function.
   */
  public function testCallEventSubscriber() {
    $event = new EventStub();
    $serviceInfo = [
      dirname(__FILE__) . '/CallEventSubscriberTestServiceStub.yml',
      'test_helpers.event_subscriber_stub',
    ];
    TestHelpers::callEventSubscriber(
      $serviceInfo,
      'event1',
      $event,
    );
    $this->assertEquals('value1', $event->value);

    TestHelpers::callEventSubscriber(
      $serviceInfo,
      'event2',
      $event,
    );
    $this->assertEquals('value2', $event->value);

    $eventSubscriberStubMocked = TestHelpers::createPartialMock(EventSubscriberStub::class, ['method2']);
    TestHelpers::setMockedClassMethod($eventSubscriberStubMocked, 'method2', function ($event) {
      $event->value = 'value2mocked';
    });
    TestHelpers::callEventSubscriber(
      $eventSubscriberStubMocked,
      'event2',
      $event,
    );
    $this->assertEquals('value2mocked', $event->value);

    TestHelpers::service('string_translation');
    TestHelpers::callEventSubscriber(
      $serviceInfo,
      'event3',
      $event,
    );
    $this->assertEquals('value2', $event->value);

    // The case with just the service name as an argument is tested in the
    // Drupal\Tests\test_helpers_example\Unit\ConfigEventsSubscriberTest()
    // because it requires a 'services.yml' file to be present, but for this
    // module it is not needed.
  }

  /**
   * Tests the callEventSubscriber function with no tag.
   */
  public function testCallEventSubscriberWithNoTag() {
    $event = new EventStub();
    $serviceInfo = [
      dirname(__FILE__) . '/CallEventSubscriberTestServiceStub.yml',
      'test_helpers.event_subscriber_stub_no_tag',
    ];
    try {
      TestHelpers::callEventSubscriber(
      $serviceInfo,
      'event3',
      $event,
      );
      $this->fail('An exception should be thrown.');
    }
    catch (\Exception) {
      $this->assertTrue(TRUE);
    }
  }

}

/**
 * A helper class for testing.
 */
#[CoversClass(\Drupal\test_helpers\TestHelpers::class)]
#[Group('test_helpers')]
class EventStub {

  /**
   * A variable.
   *
   * @var mixed
   */
  public $value;

}

/**
 * A helper class with interface for testing.
 */
#[CoversClass(\Drupal\test_helpers\TestHelpers::class)]
#[Group('test_helpers')]
class EventSubscriberStub implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'event1' => 'method1',
      'event2' => ['method2', 10],
      'event3' => [
        ['method2', 100],
        ['method1', 50],
        ['method3', 10],
      ],
      'event4' => ['method2'],
    ];
  }

  /**
   * Method test function 1.
   */
  public function method1($event) {
    $event->value = 'value1';
  }

  /**
   * Method test function 2.
   */
  public function method2($event) {
    $event->value = 'value2';
  }

  /**
   * Method test function 3.
   */
  public function method3($event) {
    $event->value = $this->t('value3');
  }

}
