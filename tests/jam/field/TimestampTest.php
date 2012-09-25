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
		$this->assertSame($expected, $field->convert(Jam::factory('test_post'), $value, FALSE));
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
		$post = Jam::factory('test_post')
			->set(array(
				'name' => 'test post',
				'slug' => 'test-post',
			));

		// Save time so we can sanity check the created timestamp
		$time = time();
		$post->save();

		// Test timestamp has been set on create
		$this->assertInternalType('integer', $post->created);
		$this->assertGreaterThanOrEqual($time, $post->created);

		// Store created so we can prove it doesn't change on update
		$created = $post->created;

		sleep(1); // Wait one second to ensure the next tests are valid
		$post->save();

		$this->assertInternalType('integer', $post->updated);
		$this->assertGreaterThan($post->created, $post->updated);
		$this->assertEquals($created, $post->created);

		// Clean up to ensure other tests don't fail
		 $post->delete();
	}

} // End Jam_Field_TimestampTest