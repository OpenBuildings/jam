<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.confirmed
 */
class Jam_Validator_ConfirmedTest extends Unittest_Jam_TestCase {

	public function test_validate()
	{
		$element = Jam::factory('test_element', 1);

		Jam::validator_rule('confirmed', array())->validate($element, 'name', $element->name);

		$this->assertHasError($element, 'name', 'confirmed');

		$element2 = Jam::factory('test_element', 2);
		$element2->name_confirmation = 'test';

		Jam::validator_rule('confirmed', array())->validate($element2, 'name', $element2->name);

		$this->assertHasError($element2, 'name', 'confirmed');

		$element3 = Jam::factory('test_element', 2);
		$element3->name_confirmation = $element3->name;

		Jam::validator_rule('confirmed', array())->validate($element3, 'name', $element3->name);

		$this->assertNotHasError($element3, 'name', 'confirmed');
	}
}