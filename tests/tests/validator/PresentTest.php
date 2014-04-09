<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.present
 */
class Jam_Validator_PresentTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			array('', FALSE, FALSE),
			array('  ', FALSE, FALSE),
			array(NULL, FALSE, FALSE),
			array(0, FALSE, FALSE),
			array(1, FALSE, TRUE),
			array('teststring', FALSE, TRUE),
			array(0, TRUE, TRUE),
			array('0', TRUE, TRUE),
			array('', TRUE, FALSE),
			array(1, TRUE, TRUE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $allow_zero, $is_valid)
	{
		$element = Jam::build('test_element');
		$validator_rule = Jam::validator_rule('present');
		$validator_rule->allow_zero = $allow_zero;

		$this->assertEquals(array('required' => TRUE), $validator_rule->html5_validation());

		$validator_rule->validate($element, 'url', $value);

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
