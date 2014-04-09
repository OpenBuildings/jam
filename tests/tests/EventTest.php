<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Timezone.
 *
 * @package Jam
 * @group   jam
 * @group   jam.event
 */
class Jam_EventTest extends PHPUnit_Framework_TestCase {

	public $event;

	public function setUp()
	{
		parent::setUp();
		$this->event = new Jam_Event('test_position');
	}

	public function test_bind()
	{
		$sender = new stdClass;

		$observer = $this->getMock('Jam_EventTest_Observer', array('callback'));
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
