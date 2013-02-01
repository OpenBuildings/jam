<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.length
 */
class Jam_Validator_LengthTest extends Unittest_Jam_TestCase {

	public function data_validate()
	{
		return array(
			// MINIMUM
			array('', array('minimum' => 4), 'length_minimum', FALSE),
			array('123', array('minimum' => 4), 'length_minimum', FALSE),
			array('1234', array('minimum' => 4), 'length_minimum', TRUE),

			// MAXIMUM
			array('12345678', array('maximum' => 4), 'length_maximum', FALSE),
			array('12345', array('maximum' => 4), 'length_maximum', FALSE),
			array('1234', array('maximum' => 4), 'length_maximum', TRUE),

			// WITHIN
			array('', array('between' => array(2, 4)), 'length_between', FALSE),
			array('1', array('between' => array(2, 4)), 'length_between', FALSE),
			array('123', array('between' => array(2, 4)), 'length_between', TRUE),
			array('1234', array('between' => array(2, 4)), 'length_between', TRUE),
			array('12345', array('between' => array(2, 4)), 'length_between', FALSE),
			array('123456', array('between' => array(2, 4)), 'length_between', FALSE),

			// IS
			array('', array('is' => 4), 'length_is', FALSE),
			array('12', array('is' => 4), 'length_is', FALSE),
			array('1234', array('is' => 4), 'length_is', TRUE),
			array('12345', array('is' => 4), 'length_is', FALSE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $is_valid)
	{
		$element = Jam::build('test_element');

		Jam::validator_rule('length', $options)->validate($element, 'name', $value);

		if ($is_valid)
		{
			$this->assertNotHasError($element, 'name', $error);
			
		}
		else
		{
			$this->assertHasError($element, 'name', $error);
		}
	}
}