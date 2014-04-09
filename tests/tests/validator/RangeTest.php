<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.range
 */
class Jam_Validator_RangeTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			// MINIMUM
			array(array(2, 5), array('minimum' => 4), 'range_minimum', FALSE),
			array(array(5, 7), array('minimum' => 4), 'range_minimum', TRUE),

			array(array(5, 2), array('minimum' => 4), 'range_minimum', FALSE),
			array(array(7, 5), array('minimum' => 4), 'range_minimum', TRUE),

			// MAXIMUM
			array(array(5, 10), array('maximum' => 6), 'range_maximum', FALSE),
			array(array(5, 10), array('maximum' => 15), 'range_maximum', TRUE),

			array(array(10, 5), array('maximum' => 6), 'range_maximum', FALSE),
			array(array(10, 5), array('maximum' => 15), 'range_maximum', TRUE),

			// BETWEEN
			array(array(5, 10), array('between' => array(2, 5)), 'range_between', FALSE),
			array(array(5, 10), array('between' => array(4, 15)), 'range_between', TRUE),
			array(array(5, 10), array('between' => array(2, 7)), 'range_between', FALSE),
			array(array(5, 10), array('between' => array(9, 15)), 'range_between', FALSE),

			// CONSECUTIVE
			array(array(10, 5), array('consecutive' => TRUE), 'range_consecutive', FALSE),
			array(array(5, 10), array('consecutive' => TRUE), 'range_consecutive', TRUE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $errors, $is_valid)
	{
		$element = Jam::build('test_element');

		$validator_rule = Jam::validator_rule('range', $options);

		$validator_rule->validate($element, 'name', new Jam_Range($value));

		if ($is_valid)
		{
			foreach ( (array) $errors as $error)
			{
				$this->assertNotHasError($element, 'name', $error);
			}
		}
		else
		{
			foreach ( (array) $errors as $error)
			{
				$this->assertHasError($element, 'name', $error);
			}
		}
	}
}
