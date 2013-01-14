<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.present
 */
class Jam_Validator_PresentTest extends Unittest_Jam_TestCase {

	public function data_validate()
	{
		return array(
			array('', FALSE),
			array('  ', FALSE),
			array(NULL, FALSE),
			array(0, FALSE),
			array(1, TRUE),
			array('teststring', TRUE)
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $is_valid)
	{
		$element = Jam::build('test_element');

		Jam::validator_rule('present')->validate($element, 'url', $value);

		if ($is_valid)
		{
			$this->assertNotHasError($element, 'url', 'present');
		}
		else
		{
			$this->assertHasError($element, 'url', 'present');
		}
	}
}