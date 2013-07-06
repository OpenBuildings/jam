<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests password fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 * @group   jam.field.password
 */
class Jam_Field_PasswordTest extends PHPUnit_Framework_TestCase {

	public function data_change_password()
	{
		return array(
			array('abc', 'a9993e364706816aba3e25717850c26c9cd0d89d'),
			array('a9993e364706816aba3e25717850c26c9cd0d89d', 'a9993e364706816aba3e25717850c26c9cd0d89d'),
			array(NULL, NULL),
		);
	}
	/**
	 * @dataProvider data_change_password
	 */
	public function test_change_password($password, $expected_hashed)
	{
		$field = new Jam_Field_Password();
		$model = Jam::build('test_position');

		$hashed = $field->convert($model, $password, FALSE);

		$this->assertEquals($expected_hashed, $hashed);
	}
}



