<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.confirmed
 */
class Jam_Validator_ConfirmedTest extends Testcase_Validate {

	public function test_validate()
	{
		$element1 = Jam::build('test_element')->load_fields(array('id' => 1, 'name' => 'Part 1'));

		Jam::validator_rule('confirmed', array())->validate($element1, 'name', $element1->name);

		$this->assertNotHasError($element1, 'name', 'confirmed');

		$element2 = Jam::build('test_element')->load_fields(array('id' => 2, 'name' => 'Part 2'));
		$element2->name_confirmation = 'test';

		Jam::validator_rule('confirmed', array())->validate($element2, 'name', $element2->name);

		$this->assertHasError($element2, 'name', 'confirmed');

		$element3 = Jam::build('test_element')->load_fields(array('id' => 2, 'name' => 'Part 2'));
		$element3->name_confirmation = $element3->name;

		Jam::validator_rule('confirmed', array())->validate($element3, 'name', $element3->name);

		$this->assertNotHasError($element3, 'name', 'confirmed');
	}
}
