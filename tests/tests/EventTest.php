<?php defined('SYSPATH') OR die('No direct script access.');

use PHPUnit\Framework\TestCase;

/**
 * Tests for Jam_Timezone.
 *
 * @package Jam
 * @group   jam
 * @group   jam.event
 */
class Jam_EventTest extends TestCase {

	public $event;

	public function setUp()
	{
		parent::setUp();
		$this->event = new Jam_Event('test_position');
	}

	public function test_bind()
	{
		$sender = new stdClass;

		$observer = $this
			->getMockBuilder(Jam_EventTest_Observer::class)
			->disableOriginalConstructor()
			->setMethods(['callback'])
			->getMock();

		$observer
			->expects($this->once())
			->method('callback')
			->with($this->equalTo($sender), $this->isInstanceOf('Jam_Event_Data'));

		$this->event->bind('test_event', array($observer, 'callback'));

		$this->event->trigger('test_event', $sender);
	}

}

class Jam_EventTest_Observer {

}
