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
			array('', array('minimum' => 4), 'length_minimum', array(
				'pattern' => '.{4,}',
				'title' => 'Value must be longer than 4 letters',
			), FALSE),
			array('123', array('minimum' => 4), 'length_minimum', array(
				'pattern' => '.{4,}',
				'title' => 'Value must be longer than 4 letters',
			), FALSE),
			array('1234', array('minimum' => 4), 'length_minimum', array(
				'pattern' => '.{4,}',
				'title' => 'Value must be longer than 4 letters',
			), TRUE),

			// MAXIMUM
			array('12345678', array('maximum' => 4), 'length_maximum', array(
				'pattern' => '.{0,4}',
				'title' => 'Value must be shorter than 4 letters',
			), FALSE),
			array('12345', array('maximum' => 4), 'length_maximum', array(
				'pattern' => '.{0,4}',
				'title' => 'Value must be shorter than 4 letters',
			), FALSE),
			array('1234', array('maximum' => 4), 'length_maximum', array(
				'pattern' => '.{0,4}',
				'title' => 'Value must be shorter than 4 letters',
			), TRUE),

			// WITHIN
			array('', array('between' => array(2, 4)), 'length_between', array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),
			array('1', array('between' => array(2, 4)), 'length_between', array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),
			array('123', array('between' => array(2, 4)), 'length_between', array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), TRUE),
			array('1234', array('between' => array(2, 4)), 'length_between', array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), TRUE),
			array('12345', array('between' => array(2, 4)), 'length_between', array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),
			array('123456', array('between' => array(2, 4)), 'length_between', array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),

			// MINIMUM AND MAXIMUM
			array('', array('minimum' => 2, 'maximum' => 4), array('length_minimum'), array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),
			array('1', array('minimum' => 2, 'maximum' => 4), array('length_minimum'), array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),
			array('123', array('minimum' => 2, 'maximum' => 4), array('length_minimum', 'length_maximum'), array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), TRUE),
			array('1234', array('minimum' => 2, 'maximum' => 4), array('length_minimum', 'length_maximum'), array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), TRUE),
			array('12345', array('minimum' => 2, 'maximum' => 4), array('length_maximum'), array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),
			array('123456', array('minimum' => 2, 'maximum' => 4), array('length_maximum'), array(
				'pattern' => '.{2,4}',
				'title' => 'Value must be longer than 2 and shorter than 4 letters',
			), FALSE),

			// IS
			array('', array('is' => 4), 'length_is', array(
				'pattern' => '.{4}',
				'title' => 'Value must be 4 letters',
			), FALSE),
			array('12', array('is' => 4), 'length_is', array(
				'pattern' => '.{4}',
				'title' => 'Value must be 4 letters',
			), FALSE),
			array('1234', array('is' => 4), 'length_is', array(
				'pattern' => '.{4}',
				'title' => 'Value must be 4 letters',
			), TRUE),
			array('12345', array('is' => 4), 'length_is', array(
				'pattern' => '.{4}',
				'title' => 'Value must be 4 letters',
			), FALSE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $errors, $expected_attributes, $is_valid)
	{
		$element = Jam::build('test_element');

		$validator_rule = Jam::validator_rule('length', $options);

		$this->assertEquals($expected_attributes, $validator_rule->html5_validation());

		$validator_rule->validate($element, 'name', $value);

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
