<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.numeric
 */
class Jam_Validator_NumericTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			// BASIC
			array('', array(), 'numeric', array('type' => 'number', 'step' => 'any'), FALSE),
			array('asdf', array(), 'numeric', array('type' => 'number', 'step' => 'any'), FALSE),
			array('12', array(), 'numeric', array('type' => 'number', 'step' => 'any'), TRUE),

			// Greater Than Or Equal To
			array(12, array('only_integer' => TRUE), 'numeric_only_integer', array('type' => 'number'), TRUE),
			array(12.4, array('only_integer' => TRUE), 'numeric_only_integer', array('type' => 'number'), FALSE),
			array('12', array('only_integer' => TRUE), 'numeric_only_integer', array('type' => 'number'), TRUE),
			array('12.123', array('only_integer' => TRUE), 'numeric_only_integer', array('type' => 'number'), FALSE),

			// Greater Than Or Equal To
			array(12, array('greater_than_or_equal_to' => 10), 'numeric_greater_than_or_equal_to', array('type' => 'number', 'step' => 'any', 'min' => 10), TRUE),
			array(12, array('greater_than_or_equal_to' => 12), 'numeric_greater_than_or_equal_to', array('type' => 'number', 'step' => 'any', 'min' => 12), TRUE),
			array(12, array('greater_than_or_equal_to' => 20), 'numeric_greater_than_or_equal_to', array('type' => 'number', 'step' => 'any', 'min' => 20), FALSE),

			// Greater Than 
			array(12, array('greater_than' => 10), 'numeric_greater_than', array('type' => 'number', 'step' => 'any'), TRUE),
			array(12, array('greater_than' => 20), 'numeric_greater_than', array('type' => 'number', 'step' => 'any'), FALSE),
			array(12, array('greater_than' => 20, 'only_integer' => TRUE), 'numeric_greater_than', array('type' => 'number', 'min' => 21), FALSE),

			// Equal To
			array(12, array('equal_to' => 10), 'numeric_equal_to', array('type' => 'number', 'step' => 'any'), FALSE),
			array(12, array('equal_to' => 20), 'numeric_equal_to', array('type' => 'number', 'step' => 'any'), FALSE),
			array(12, array('equal_to' => 12), 'numeric_equal_to', array('type' => 'number', 'step' => 'any'), TRUE),

			// Greater Than
			array(12, array('less_than' => 20), 'numeric_less_than', array('type' => 'number', 'step' => 'any'), TRUE),
			array(12, array('less_than' => 10), 'numeric_less_than', array('type' => 'number', 'step' => 'any'), FALSE),
			array(12, array('less_than' => 10, 'only_integer' => TRUE), 'numeric_less_than', array('type' => 'number', 'max' => 9), FALSE),

			// Greater Than
			array(12, array('less_than_or_equal_to' => 20), 'numeric_less_than_or_equal_to', array('type' => 'number', 'step' => 'any', 'max' => 20), TRUE),
			array(12, array('less_than_or_equal_to' => 12), 'numeric_less_than_or_equal_to', array('type' => 'number', 'step' => 'any', 'max' => 12), TRUE),
			array(12, array('less_than_or_equal_to' => 10), 'numeric_less_than_or_equal_to', array('type' => 'number', 'step' => 'any', 'max' => 10), FALSE),

			// Odd
			array(12, array('odd' => TRUE), 'numeric_odd', array('type' => 'number', 'step' => 'any'), TRUE),
			array(11, array('odd' => TRUE), 'numeric_odd', array('type' => 'number', 'step' => 'any'), FALSE),
			array(10, array('odd' => TRUE), 'numeric_odd', array('type' => 'number', 'step' => 'any'), TRUE),
			array(3, array('odd' => TRUE), 'numeric_odd', array('type' => 'number', 'step' => 'any'), FALSE),

			// Odd
			array(13, array('even' => TRUE), 'numeric_even', array('type' => 'number', 'step' => 'any'), TRUE),
			array(12, array('even' => TRUE), 'numeric_even', array('type' => 'number', 'step' => 'any'), FALSE),
			array(11, array('even' => TRUE), 'numeric_even', array('type' => 'number', 'step' => 'any'), TRUE),
			array(2, array('even' => TRUE), 'numeric_even', array('type' => 'number', 'step' => 'any'), FALSE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $expected_attributes, $is_valid)
	{
		$element = Jam::build('test_element');
		$validator_rule = Jam::validator_rule('numeric', $options);

		$this->assertEquals($expected_attributes, $validator_rule->html5_validation());
		$validator_rule->validate($element, 'amount', $value);

		if ($is_valid)
		{
			$this->assertNotHasError($element, 'amount', $error);
		}
		else
		{
			$this->assertHasError($element, 'amount', $error);
		}
	}
}