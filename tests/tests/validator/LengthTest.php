<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.length
 */
class Jam_Validator_LengthTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			// MINIMUM
			array('', array('minimum' => 4), 'length_minimum', array('pattern' => '.{4,}'), FALSE),
			array('123', array('minimum' => 4), 'length_minimum', array('pattern' => '.{4,}'), FALSE),
			array('1234', array('minimum' => 4), 'length_minimum', array('pattern' => '.{4,}'), TRUE),

			// MAXIMUM
			array('12345678', array('maximum' => 4), 'length_maximum', array('pattern' => '.{,4}'), FALSE),
			array('12345', array('maximum' => 4), 'length_maximum', array('pattern' => '.{,4}'), FALSE),
			array('1234', array('maximum' => 4), 'length_maximum', array('pattern' => '.{,4}'), TRUE),

			// WITHIN
			array('', array('between' => array(2, 4)), 'length_between', array('pattern' => '.{2,4}'), FALSE),
			array('1', array('between' => array(2, 4)), 'length_between', array('pattern' => '.{2,4}'), FALSE),
			array('123', array('between' => array(2, 4)), 'length_between', array('pattern' => '.{2,4}'), TRUE),
			array('1234', array('between' => array(2, 4)), 'length_between', array('pattern' => '.{2,4}'), TRUE),
			array('12345', array('between' => array(2, 4)), 'length_between', array('pattern' => '.{2,4}'), FALSE),
			array('123456', array('between' => array(2, 4)), 'length_between', array('pattern' => '.{2,4}'), FALSE),

			// IS
			array('', array('is' => 4), 'length_is', array('pattern' => '.{4}'), FALSE),
			array('12', array('is' => 4), 'length_is', array('pattern' => '.{4}'), FALSE),
			array('1234', array('is' => 4), 'length_is', array('pattern' => '.{4}'), TRUE),
			array('12345', array('is' => 4), 'length_is', array('pattern' => '.{4}'), FALSE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $expected_attributes, $is_valid)
	{
		$element = Jam::build('test_element');

		$validator_rule = Jam::validator_rule('length', $options);

		$this->assertEquals($expected_attributes, $validator_rule->html5_validation());

		$validator_rule->validate($element, 'name', $value);

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