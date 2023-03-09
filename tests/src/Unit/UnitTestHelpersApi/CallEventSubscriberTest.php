<?php

namespace Drupal\Tests\test_helpers\Unit\UnitTestHelpersApi;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\TestHelpers;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests CreateEntityStub API function.
 *
 * @coversDefaultClass \Drupal\test_helpers\TestHelpers
 * @group test_helpers
 */
class CallEventSubscriberTest extends UnitTestCase {

  /**
   * @covers ::callEventSubscriber
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

    TestHelpers::service('string_translation');
    TestHelpers::callEventSubscriber(
      $serviceInfo,
      'event3',
      $event,
    );
    $this->assertEquals('value2', $event->value);

    // The case with just the service name as an argument is tested in the
    // Drupal\Tests\test_helpers_example\Unit\ConfigEventsSubscriberTest()
    // because it requires a 'services.yml' file to be presend, but for this
    // module it is not needed.
  }

  /**
   * @covers ::callEventSubscriber
   */
  public function testCallEventSubscriberWithNoTag() {
    $event = new EventStub();
    $serviceInfo = [
      'yml' => dirname(__FILE__) . '/CallEventSubscriberTestServiceStub.yml',
      'service' => 'test_helpers.event_subscriber_stub_no_tag',
    ];
    try {
      TestHelpers::callEventSubscriber(
        $serviceInfo,
        'event3',
        $event,
      );
      $this->fail('An exception should be thown.');
    }
    catch (\Exception $e) {
    }
  }

}
/**
 * A helper class for testing.
 */
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
class EventSubscriberStub implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
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
