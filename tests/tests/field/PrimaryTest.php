<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests primary fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 * @group   jam.field.primary
 */
class Jam_Field_PrimaryTest extends PHPUnit_Framework_TestCase {

	/**
	 * Primary fields cannot have allow_null set to FALSE.
	 *
	 * @expectedException Kohana_Exception
	 */
	public function test_allow_null_throws_exception()
	{
		$field = new Jam_Field_Primary(array('allow_null' => FALSE));
	}
}
