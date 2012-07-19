<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 */
class Jam_ValidatorTest extends Unittest_Jam_TestCase {

	public function test_validator()
	{
		$element = Jam::factory('test_element', 1);

		$element->set(array(
			'name' => NULL,
			'email' => 'invalidemail',
			'amount' => 2,
			'description' => 'short',
		));

		$element->check();

		$this->assertHasError($element, 'name', 'present');
		$this->assertHasError($element, 'email', 'format_filter');
		$this->assertHasError($element, 'amount', 'numeric_greater_than');
		$this->assertHasError($element, 'description', 'length_within');
	}

	public function test_condition()
	{
		$element = Jam::factory('test_element', 1);

		$element->name_is_ip = TRUE;

		$element->name = 'test';
		$element->check();
		$this->assertHasError($element, 'name', 'format_filter');

		$element->revert();
		$element->name = '95.87.212.88';
		$element->check();
		$this->assertNotHasError($element, 'name', 'format_filter');

		$element->name_is_ip = FALSE;
		$element->revert();
		$element->name = 'test';
		$element->check();
		$this->assertNotHasError($element, 'name', 'format_filter');

		$element->name_is_email = TRUE;
		$element->revert();
		$element->name = 'notemail';
		$element->check();
		$this->assertHasError($element, 'name', 'format_filter');

		$element->revert();
		$element->name = 'email@example.com';
		$element->check();
		$this->assertNotHasError($element, 'name', 'format_filter');

	}
}