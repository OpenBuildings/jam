<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests primary fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 * @group   jam.field.boolean
 */
class Jam_Field_BooleanTest extends PHPUnit_Framework_TestCase {

	/**
	 * Boolean fields cannot have convert_empty set to TRUE.
	 *
	 * @expectedException Kohana_Exception
	 */
	public function test_convert_empty_throws_exception()
	{
		$field = new Jam_Field_Boolean(array('convert_empty' => TRUE));
	}
}
