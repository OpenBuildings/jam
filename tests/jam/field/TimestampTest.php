<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests timestamp fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 * @group   jam.field.timestamp
 */
class Jam_Field_TimestampTest extends Unittest_TestCase {

	/**
	 * Provider for test_format
	 */
	public function provider_format()
	{
		$field = new Jam_Field_Timestamp(array('format' => 'Y-m-d H:i:s', 'timezone' => new Jam_Timezone()));
		
		return array(
			array($field, "2010-03-15 05:45:00", "2010-03-15 05:45:00"),
			array($field, 1268657100, "2010-03-15 05:45:00"),
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
		$this->assertSame($expected, $field->convert(NULL, $value, FALSE));
	}

	public function test_timezone()
	{
		$field = new Jam_Field_Timestamp(array('format' => 'Y-m-d H:i:s'));
		$field->timezone = new Jam_Timezone();
		$field->timezone
			->user_timezone('Europe/Sofia')
			->default_timezone('Europe/Moscow');

		$this->assertEquals("2010-03-15 03:45:00", $field->convert(Jam::factory('test_post'), "2010-03-15 05:45:00", FALSE), 'Should set modified data to the database');
		$this->assertEquals("2010-03-15 05:45:00", $field->get(Jam::factory('test_post'), "2010-03-15 03:45:00", FALSE), 'Should load modified from the database');
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

		$this->assertEquals($default_date, $normal->convert(NULL, $default_date, TRUE), 'Should not generate a new date on normal timestamp');
		$this->assertEquals($default_date, $normal->convert(NULL, $default_date, FALSE), 'Should not generate a new date on normal timestamp');

		$this->assertGreaterThan($default_date, $auto_create->convert(NULL, $default_date, FALSE), 'Should generate a new date on create');		
		$this->assertEquals($default_date, $auto_create->convert(NULL, $default_date, TRUE), 'Should not generate a new date on update');		

		$this->assertEquals($default_date, $auto_update->convert(NULL, $default_date, FALSE), 'Should generate a new date on create');		
		$this->assertGreaterThan($default_date, $auto_update->convert(NULL, $default_date, TRUE), 'Should not generate a new date on update');
	}

} // End Jam_Field_TimestampTest