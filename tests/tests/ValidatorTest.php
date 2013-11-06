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

	public function data_constructor()
	{
		return array(
			array(
				array(),
				array(),
				array(),
				NULL,
				FALSE,
				array(),
			),
			array(
				array('abc'),
				array(),
				array('abc'),
				NULL,
				FALSE,
				array(),
			),
			array(
				array('abc'),
				array(
					'present' => TRUE
				),
				array('abc'),
				NULL,
				FALSE,
				array(
					Jam::validator_rule('present')
				),
			),
			array(
				array('abc'),
				array(
					'if' => 'abc'
				),
				array('abc'),
				'abc',
				FALSE,
				array(),
			),
			array(
				array('abc'),
				array(
					'unless' => 'abc'
				),
				array('abc'),
				'abc',
				TRUE,
				array(),
			),
			array(
				array('abc'),
				array(
					'unless' => 'abc',
					'present' => TRUE,
					'format' => array(
						'email' => TRUE
					)
				),
				array('abc'),
				'abc',
				TRUE,
				array(
					Jam::validator_rule('present'),
					Jam::validator_rule('format', array('email' => TRUE))
				),
			),
			array(
				array('abc', 'qwe'),
				array(
					'unless' => 'qwe',
					'if' => 'abc',
					'present' => TRUE,
					'format' => array(
						'email' => TRUE
					)
				),
				array('abc', 'qwe'),
				'qwe',
				TRUE,
				array(
					Jam::validator_rule('present'),
					Jam::validator_rule('format', array('email' => TRUE))
				),
			),
		);
	}

	/**
	 * @dataProvider data_constructor
	 * @covers Jam_Validator::__construct
	 */
	public function test_constructor($attributes, $options, $expected_attributes, $expected_condition, $expected_condition_negative, $expected_rules)
	{
		$validator = new Jam_Validator($attributes, $options);
		$this->assertEquals($expected_attributes, $validator->attributes);
		$this->assertEquals($expected_condition, $validator->condition);
		$this->assertEquals($expected_condition_negative, $validator->condition_negative);
		$this->assertEquals($expected_rules, $validator->rules);
	}

	/**
	 * @coversNothing
	 */
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

	/**
	 * Integration tests for Jam_Validator::condition_met()
	 *
	 * @coversNothing
	 */
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

	public function data_condition_met()
	{
		return array(
			array(
				array('abc'),
				array(
					'present' => TRUE,
				),
				array(),
				TRUE,
			),
			array(
				array('abc'),
				array(
					'present' => TRUE,
					'if' => 'qwerty'
				),
				array(),
				FALSE,
			),
			array(
				array('abc'),
				array(
					'present' => TRUE,
					'if' => 'qwerty'
				),
				array(
					'qwerty' => FALSE,
				),
				FALSE,
			),
			array(
				array('abc'),
				array(
					'present' => TRUE,
					'if' => 'qwerty'
				),
				array(
					'qwerty' => TRUE,
				),
				TRUE,
			),
			array(
				array('abc'),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'if' => 'qwerty'
				),
				array(
					'qwerty' => FALSE,
				),
				FALSE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'if' => 'name_is_normal()'
				),
				array(
					'name_is_email' => FALSE
				),
				TRUE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'if' => 'name_is_normal()'
				),
				array(
					'name_is_email' => TRUE
				),
				FALSE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'if' => 'Jam_ValidatorTest::is_email_set'
				),
				array(),
				FALSE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'if' => 'Jam_ValidatorTest::is_email_set'
				),
				array(
					'email' => 'email@example.com'
				),
				TRUE,
			),
			array(
				array('abc'),
				array(
					'present' => TRUE,
					'unless' => 'qwerty'
				),
				array(),
				TRUE,
			),
			array(
				array('abc'),
				array(
					'present' => TRUE,
					'unless' => 'qwerty'
				),
				array(
					'qwerty' => FALSE,
				),
				TRUE,
			),
			array(
				array('abc'),
				array(
					'present' => TRUE,
					'unless' => 'qwerty'
				),
				array(
					'qwerty' => TRUE,
				),
				FALSE,
			),
			array(
				array('abc'),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'unless' => 'qwerty'
				),
				array(
					'qwerty' => FALSE,
				),
				TRUE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'unless' => 'name_is_normal()'
				),
				array(
					'name_is_email' => FALSE
				),
				FALSE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'unless' => 'name_is_normal()'
				),
				array(
					'name_is_email' => TRUE
				),
				TRUE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'unless' => 'Jam_ValidatorTest::is_email_set'
				),
				array(),
				TRUE,
			),
			array(
				array(
					'abc',
				),
				array(
					'format' => array(
						'email' => TRUE,
					),
					'unless' => 'Jam_ValidatorTest::is_email_set'
				),
				array(
					'email' => 'email@example.com'
				),
				FALSE,
			),
		);
	}

	public static function is_email_set(Jam_Validated $model, array $attributes)
	{
		return (bool) $model->email;
	}

	/**
	 * @dataProvider data_condition_met
	 * @covers Jam_Validator::condition_met
	 */
	public function test_condition_met(array $attributes, array $options, array $fields, $expected)
	{
		$validator = new Jam_Validator($attributes, $options);
		$model = Jam::build('test_element');

		foreach ($fields as $key => $value)
		{
			$model->$key = $value;
		}

		$this->assertSame($expected, $validator->condition_met($model));
	}
}
