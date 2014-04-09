<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.unique
 */
class Jam_Validator_UniqueTest extends Testcase_Validate {

	public function test_validate()
	{
		$element = Jam::find('test_element', 1);

		Jam::validator_rule('unique', array())->validate($element, 'name', $element->name);

		$this->assertNotHasError($element, 'name', 'unique');

		$element2 = Jam::find('test_element', 2);

		Jam::validator_rule('unique', array())->validate($element2, 'url', $element2->url);

		$this->assertHasError($element2, 'url', 'unique');

		$element3 = Jam::find('test_element', 2);

		Jam::validator_rule('unique', array('scope' => 'name'))->validate($element3, 'url', $element3->url);

		$this->assertNotHasError($element3, 'url', 'unique');
	}
}
