<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 */
class Jam_ValidatorTest extends Testcase_Validate {

	public $element;

	public function setUp()
	{
		parent::setUp();
		$this->element = Jam::build('test_element')->load_fields(array(
			'id' => 1, 
			'name' => 'Part 1', 
			'email' => 'staff@example.com',
			'url' => 'http://parts.wordpress.com/',
			'desceription' => 'Big Part',
			'amount' => 20,
			'test_author_id' => 1
		));
	}

	public function test_validator()
	{
		$this->element->set(array(
			'name' => NULL,
			'email' => 'invalidemail',
			'amount' => 2,
			'description' => 'short',
		));

		$this->element->check();

		$this->assertHasError($this->element, 'name', 'present');
		$this->assertHasError($this->element, 'email', 'format_filter');
		$this->assertHasError($this->element, 'amount', 'numeric_greater_than');
		$this->assertHasError($this->element, 'description', 'length_between');
		
		$this->assertNotHasError($this->element, 'name', 'length_minimum');
	}

	public function test_condition()
	{
		$this->element->name_is_ip = TRUE;

		$this->element->name = 'test';
		$this->element->check();
		$this->assertHasError($this->element, 'name', 'format_filter');

		$this->element->revert();
		$this->element->name = '95.87.212.88';
		$this->element->check();
		$this->assertNotHasError($this->element, 'name', 'format_filter');

		$this->element->name_is_ip = FALSE;
		$this->element->revert();
		$this->element->name = 'test';
		$this->element->check();
		$this->assertNotHasError($this->element, 'name', 'format_filter');

		$this->element->name_is_email = TRUE;
		$this->element->revert();
		$this->element->name = 'notemail';
		$this->element->check();
		$this->assertHasError($this->element, 'name', 'format_filter');

		$this->element->revert();
		$this->element->name = 'email@example.com';
		$this->element->check();
		$this->assertNotHasError($this->element, 'name', 'format_filter');

	}
}