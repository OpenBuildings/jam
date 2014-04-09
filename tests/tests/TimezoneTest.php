<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Timezone.
 *
 * @package Jam
 * @group   jam
 * @group   jam.timezone
 */
class Jam_TimezoneTest extends PHPUnit_Framework_TestCase {

	public $date;
	public $timezone;

	public function setUp()
	{
		parent::setUp();
		$this->timezone = new Jam_Timezone();

		$this->timezone
			->default_timezone('Europe/Sofia')
			->user_timezone('Europe/Moscow');

		$this->date = 1268649900;
	}

	public function data_shift()
	{
		return array(
			array('Europe/Sofia', 'Europe/Moscow', +1),
			array('UTC', 'Europe/Moscow', +3),
			array('Europe/Sofia', 'UTC', -2),
		);
	}

	/**
	 * @dataProvider data_shift
	 */
	public function test_shift($from_timezone, $to_timezone, $offset)
	{
		$shifted_date = Jam_Timezone::shift($this->date, new DateTimeZone($from_timezone), new DateTimeZone($to_timezone));

		$this->assertEquals($offset, ($shifted_date - $this->date) / 3600);
	}

	public function test_default_timezone()
	{
		$timezone = new Jam_Timezone();
		$this->assertEquals(date_default_timezone_get(), $timezone->default_timezone()->getName());
	}

	public function test_master_timezone()
	{
		$timezone = new Jam_Timezone();
		$this->assertEquals('UTC', $timezone->master_timezone()->getName());
	}

	public function test_date()
	{
		$unchanged_timezone = new Jam_Timezone();

		$this->assertEquals(date('c', $this->date), $unchanged_timezone->date('c', $this->date));
		$this->assertEquals(date('c', $this->date - 3600*2), $this->timezone->date('c', $this->date));
	}

}
