<?php

namespace Drupal\Tests\test_helpers\Unit\UnitTestHelpersApi;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\test_helpers\UnitTestHelpers;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests CreateEntityStub API function.
 *
 * @coversDefaultClass \Drupal\test_helpers\UnitTestHelpersTest
 * @group test_helpers
 */
class CallEventSubscriberTest extends UnitTestCase {

  /**
   * @covers ::callEventSubscriber
   */
  public function testCallEventSubscriber() {
    $event = new EventStub();
    UnitTestHelpers::callEventSubscriber(
      dirname(__FILE__) . '/CallEventSubscriberTestServiceStub.yml',
      'test_helpers.event_subscriber_stub',
      'event1',
      $event,
    );
    $this->assertEquals('value1', $event->value);

    UnitTestHelpers::callEventSubscriber(
      dirname(__FILE__) . '/CallEventSubscriberTestServiceStub.yml',
      'test_helpers.event_subscriber_stub',
      'event2',
      $event,
    );
    $this->assertEquals('value2', $event->value);

    UnitTestHelpers::service('string_translation');
    UnitTestHelpers::callEventSubscriber(
      dirname(__FILE__) . '/CallEventSubscriberTestServiceStub.yml',
      'test_helpers.event_subscriber_stub',
      'event3',
      $event,
    );
    $this->assertEquals('value2', $event->value);
  }

  /**
   * @covers ::callEventSubscriber
   */
  public function testCallEventSubscriberWithNoTag() {
    $event = new EventStub();
    try {
      UnitTestHelpers::callEventSubscriber(
        dirname(__FILE__) . '/CallEventSubscriberTestServiceStub.yml',
        'test_helpers.event_subscriber_stub_no_tag',
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
