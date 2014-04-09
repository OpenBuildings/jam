<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.count
 */
class Jam_Validator_CountTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			// MINIMUM
			array(array(), array('minimum' => 4), 'count_minimum', FALSE),
			array(array(1,2,3), array('minimum' => 4), 'count_minimum', FALSE),
			array(array(1,2,3,4), array('minimum' => 4), 'count_minimum', TRUE),

			// MAXIMUM
			array(array(1,2,3,4,5,6,7,8), array('maximum' => 4), 'count_maximum', FALSE),
			array(array(1,2,3,4,5), array('maximum' => 4), 'count_maximum', FALSE),
			array(array(1,2,3,4), array('maximum' => 4), 'count_maximum', TRUE),

			// WITHIN
			array(array(), array('within' => array(2, 4)), 'count_within', FALSE),
			array(array(1), array('within' => array(2, 4)), 'count_within', FALSE),
			array(array(1,2,3), array('within' => array(2, 4)), 'count_within', TRUE),
			array(array(1,2,3,4), array('within' => array(2, 4)), 'count_within', TRUE),
			array(array(1,2,3,4,5), array('within' => array(2, 4)), 'count_within', FALSE),
			array(array(1,2,3,4,5,6), array('within' => array(2, 4)), 'count_within', FALSE),

			// IS
			array(array(), array('is' => 4), 'count_is', FALSE),
			array(array(1,2), array('is' => 4), 'count_is', FALSE),
			array(array(1,2,3,4), array('is' => 4), 'count_is', TRUE),
			array(array(1,2,3,4,5), array('is' => 4), 'count_is', FALSE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $is_valid)
	{
		$element = Jam::build('test_element');

		Jam::validator_rule('count', $options)->validate($element, 'count', $value);

		if ($is_valid)
		{
			$this->assertNotHasError($element, 'count', $error);

		}
		else
		{
			$this->assertHasError($element, 'count', $error);
		}
	}
}
