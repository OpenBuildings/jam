<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.choice
 */
class Jam_Validator_ChoiceTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			// IN
			array('e', array('in' => array('test1', 'test2')), 'choice_in', FALSE),
			array('test', array('in' => array('test1', 'test2')), 'choice_in', FALSE),
			array('test1', array('in' => array('test1', 'test2')), 'choice_in', TRUE),
			array('test3', array('in' => array('test1', 'test2')), 'choice_in', FALSE),

			// NOT_IN
			array('e', array('not_in' => array('test1', 'test2')), 'choice_not_in', TRUE),
			array('test', array('not_in' => array('test1', 'test2')), 'choice_not_in', TRUE),
			array('test1', array('not_in' => array('test1', 'test2')), 'choice_not_in', FALSE),
			array('test3', array('not_in' => array('test1', 'test2')), 'choice_not_in', TRUE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $is_valid)
	{
		$element = Jam::build('test_element');

		Jam::validator_rule('choice', $options)->validate($element, 'name', $value);

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
