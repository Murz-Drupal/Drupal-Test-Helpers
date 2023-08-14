<?php

namespace Drupal\Tests\test_helpers_example\Unit;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\test_helpers_example\EventSubscriber\ConfigEventsSubscriber
 * @group test_helpers_example
 */
class ConfigEventsSubscriberTest extends UnitTestCase {

  /**
   * Tests an event subscriber class.
   */
  public function testEventSubscriber() {
    $messenger = TestHelpers::service('messenger');
    $config = TestHelpers::service('config.factory')->getEditable('some.config');
    $event = new ConfigCrudEvent($config);
    $serviceName = 'test_helpers_example.config_events_subscriber';

    TestHelpers::callEventSubscriber($serviceName, ConfigEvents::SAVE, $event);
    $this->assertEquals('Saved config: some.config', $messenger->deleteAll()['status'][0]);

    TestHelpers::callEventSubscriber($serviceName, ConfigEvents::DELETE, $event);
    $this->assertEquals('Deleted config: some.config', $messenger->deleteAll()['status'][0]);
  }

}
