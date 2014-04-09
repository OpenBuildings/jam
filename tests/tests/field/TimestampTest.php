<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests timestamp fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 * @group   jam.field.timestamp
 */
class Jam_Field_TimestampTest extends PHPUnit_Framework_TestCase {

	/**
	 * Provider for test_format
	 */
	public function provider_format()
	{
		$field = new Jam_Field_Timestamp(array('format' => 'Y-m-d H:i:s', 'timezone' => new Jam_Timezone()));
		$date = strtotime("2010-03-15 05:45:00");
		return array(
			array($field, "2010-03-15 05:45:00", "2010-03-15 05:45:00"),
			array($field, $date, "2010-03-15 05:45:00"),
		);
	}

	/**
	 * Tests for issue #113 that ensures timestamps specified
	 * with a format are converted properly.
	 *
	 * @dataProvider  provider_format
	 * @link  http://github.com/jonathangeiger/kohana-jam/issues/113
	 */
	public function test_format($field, $value, $expected)
	{
		$model = Jam::build('test_position');
		$this->assertSame($expected, $field->convert($model, $value, FALSE));
	}

	public function test_timezone()
	{
		$model = Jam::build('test_position');
		$field = new Jam_Field_Timestamp(array('format' => 'Y-m-d H:i:s'));
		$field->timezone = new Jam_Timezone();
		$field->timezone
			->user_timezone('Europe/Sofia')
			->default_timezone('Europe/Moscow');

		$this->assertEquals("2010-03-15 03:45:00", $field->convert($model, "2010-03-15 05:45:00", FALSE), 'Should set modified data to the database');
		$this->assertEquals("2010-03-15 05:45:00", $field->get($model, "2010-03-15 03:45:00", FALSE), 'Should load modified from the database');
	}

	/**
	 * Tests that timestamp auto create and auto update work as expected.
	 */
	public function test_auto_create_and_update()
	{
		$normal = new Jam_Field_Timestamp(array('timezone' => new Jam_Timezone()));
		$auto_create = new Jam_Field_Timestamp(array('auto_now_create' => TRUE, 'timezone' => new Jam_Timezone()));
		$auto_update = new Jam_Field_Timestamp(array('auto_now_update' => TRUE, 'timezone' => new Jam_Timezone()));
		$default_date = 1268657100;

		$model = Jam::build('test_position');

		$this->assertEquals($default_date, $normal->convert($model, $default_date, TRUE), 'Should not generate a new date on normal timestamp');
		$this->assertEquals($default_date, $normal->convert($model, $default_date, FALSE), 'Should not generate a new date on normal timestamp');

		$this->assertGreaterThan($default_date, $auto_create->convert($model, $default_date, FALSE), 'Should generate a new date on create');
		$this->assertEquals($default_date, $auto_create->convert($model, $default_date, TRUE), 'Should not generate a new date on update');

		$this->assertEquals($default_date, $auto_update->convert($model, $default_date, FALSE), 'Should generate a new date on create');
		$this->assertGreaterThan($default_date, $auto_update->convert($model, $default_date, TRUE), 'Should not generate a new date on update');
	}

}
