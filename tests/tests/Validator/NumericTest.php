<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.numeric
 */
class Jam_Validator_NumericTest extends Unittest_Jam_TestCase {

	public function data_validate()
	{
		return array(
			// BASIC
			array('', array(), 'numeric', FALSE),
			array('asdf', array(), 'numeric', FALSE),
			array('12', array(), 'numeric', TRUE),

			// Greater Than Or Equal To
			array(12, array('only_integer' => TRUE), 'numeric_only_integer', TRUE),
			array(12.4, array('only_integer' => TRUE), 'numeric_only_integer', FALSE),
			array('12', array('only_integer' => TRUE), 'numeric_only_integer', TRUE),
			array('12.123', array('only_integer' => TRUE), 'numeric_only_integer', FALSE),

			// Greater Than Or Equal To
			array(12, array('greater_than_or_equal_to' => 10), 'numeric_greater_than_or_equal_to', TRUE),
			array(12, array('greater_than_or_equal_to' => 12), 'numeric_greater_than_or_equal_to', TRUE),
			array(12, array('greater_than_or_equal_to' => 20), 'numeric_greater_than_or_equal_to', FALSE),

			// Greater Than 
			array(12, array('greater_than' => 10), 'numeric_greater_than', TRUE),
			array(12, array('greater_than' => 20), 'numeric_greater_than', FALSE),

			// Equal To
			array(12, array('equal_to' => 10), 'numeric_equal_to', FALSE),
			array(12, array('equal_to' => 20), 'numeric_equal_to', FALSE),
			array(12, array('equal_to' => 12), 'numeric_equal_to', TRUE),

			// Greater Than
			array(12, array('less_than' => 20), 'numeric_less_than', TRUE),
			array(12, array('less_than' => 10), 'numeric_less_than', FALSE),

			// Greater Than
			array(12, array('less_than_or_equal_to' => 20), 'numeric_less_than_or_equal_to', TRUE),
			array(12, array('less_than_or_equal_to' => 12), 'numeric_less_than_or_equal_to', TRUE),
			array(12, array('less_than_or_equal_to' => 10), 'numeric_less_than_or_equal_to', FALSE),

			// Odd
			array(12, array('odd' => TRUE), 'numeric_odd', TRUE),
			array(11, array('odd' => TRUE), 'numeric_odd', FALSE),
			array(10, array('odd' => TRUE), 'numeric_odd', TRUE),
			array(3, array('odd' => TRUE), 'numeric_odd', FALSE),

			// Odd
			array(13, array('even' => TRUE), 'numeric_even', TRUE),
			array(12, array('even' => TRUE), 'numeric_even', FALSE),
			array(11, array('even' => TRUE), 'numeric_even', TRUE),
			array(2, array('even' => TRUE), 'numeric_even', FALSE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $is_valid)
	{
		$element = Jam::build('test_element');

		Jam::validator_rule('numeric', $options)->validate($element, 'amount', $value);

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