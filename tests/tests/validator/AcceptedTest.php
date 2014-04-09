<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.accepted
 */
class Jam_Validator_AcceotedTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			array('', TRUE, FALSE),
			array(0, TRUE, FALSE),
			array(1, TRUE, TRUE),
			array('teststring', TRUE, TRUE),
			array('teststring', 'test', FALSE),
			array('test', 'test', TRUE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $accept, $is_valid)
	{
		$element = Jam::build('test_element');

		Jam::validator_rule('accepted', array('accept' => $accept))->validate($element, 'name', $value);

		if ($is_valid)
		{
			$this->assertNotHasError($element, 'name', 'accepted');
		}
		else
		{
			$this->assertHasError($element, 'name', 'accepted');
		}
	}
}
