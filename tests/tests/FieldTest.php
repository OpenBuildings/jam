<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Provides a set of tests that apply to many different field types.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 */
class Jam_FieldTest extends PHPUnit_Framework_TestCase {

	/**
	 * Provider for test_construction
	 */
	public function provider_construction()
	{
		return array(

			// Primary
			array(new Jam_Field_Primary, array(
				'default' => NULL,
				'primary' => TRUE
			)),

			// Integers
			array(new Jam_Field_Integer, array(
				'default' => NULL
			)),
			array(new Jam_Field_Integer(array(
				'convert_empty' => TRUE,
			)), array(
				'null_set' => NULL,
				'default'  => NULL,
			)),
			array(new Jam_Field_Integer(array(
				'allow_null' => FALSE,
			)), array(
				'default'  => 0,
				'null_set' => 0,
			)),

			// Floats
			array(new Jam_Field_Float, array(
				'default' => NULL,
			)),
			array(new Jam_Field_Float(array(
				'allow_null' => FALSE,
			)), array(
				'default'  => 0.0,
				'null_set' => 0.0,
			)),

			// Booleans
			array(new Jam_Field_Boolean, array(
				'default'    => FALSE,
				'allow_null' => FALSE,
				'null_set'   => FALSE,
			)),
			array(new Jam_Field_Boolean(array(
				'allow_null' => TRUE,
			)), array(
				'default' => NULL,
			)),

			// Strings: They default to allow_null as false
			array(new Jam_Field_String, array(
				'default'  => '',
				'null_set' => '',
			)),
			array(new Jam_Field_String(array(
				'convert_empty' => TRUE,
			)), array(
				'default'    => NULL,
				'allow_null' => TRUE,
			)),

			// Weblink
			array(
				new Jam_Field_Weblink,
				array(
					'default'    => '',
					'allow_null' => FALSE,
					'null_set'   => ''
				)
			),

			// Twitter
			array(
				new Jam_Field_Weblink,
				array(
					'default'    => '',
					'allow_null' => FALSE,
					'null_set'   => ''
				)
				),

			// Range
			array(
				new Jam_Field_Range,
				array(
					'default' => NULL,
				)
			)
		);
	}

	/**
	 * Tests various aspects of a newly constructed field.
	 *
	 * @dataProvider provider_construction
	 */
	public function test_construction(Jam_Field $field, $expected)
	{
		$model = Jam::build('test_position');

		// Ensure the following properties have been set
		foreach ($expected as $key => $value)
		{
			// NULL set is the expected value when allow_null is TRUE, skip it
			if ($key === 'null_set') continue;

			$this->assertSame($field->$key, $value, 'Field properties must match');
		}

		// Ensure that null values are handled properly
		if ($field->allow_null)
		{
			$this->assertSame($field->set($model, NULL, TRUE), NULL,
				'Field must return NULL when given NULL since `allow_null` is TRUE');
		}
		else
		{
			$this->assertSame($field->set($model, NULL, TRUE), $expected['null_set'],
				'Since `allow_null` is FALSE, field must return expected value when given NULL');
		}

		// Ensure convert_empty works
		if ($field->convert_empty)
		{
			// allow_null must be true if convert_empty is TRUE and empty_value is NULL
			if ($field->empty_value === NULL)
			{
				$this->assertTrue($field->allow_null, 'allow_null must be TRUE since convert_empty is TRUE');
			}

			// Test setting a few empty values
			foreach (array(NULL, FALSE, '', '0', 0) as $value)
			{
				$this->assertSame($field->set($model, $value, TRUE), $field->empty_value);
			}
		}
	}

	/**
	 * Provider for test_set
	 */
	public function provider_set()
	{
		return array(
			// Primary Keys
			array(new Jam_Field_Primary, 1, 1),
			array(new Jam_Field_Primary, 'primary-key-string', 'primary-key-string'),

			// Booleans
			array(new Jam_Field_Boolean, 1, TRUE),
			array(new Jam_Field_Boolean, '1', TRUE),
			array(new Jam_Field_Boolean, 'TRUE', TRUE),
			array(new Jam_Field_Boolean, 'yes', TRUE),

			// Integers
			array(new Jam_Field_Integer, 1.1, 1),
			array(new Jam_Field_Integer, '1', 1),

			// Floats
			array(new Jam_Field_Float, 1, 1.0),
			array(new Jam_Field_Float(array('places' => 2)), 3.14157, 3.14),
			array(new Jam_Field_Float, '3.14157', 3.14157),

			// Strings
			array(new Jam_Field_String, 1, '1'),

			// Slugs
			array(new Jam_Field_Slug, 'Hello, World', 'hello-world'),

			// Serializable data
			array(new Jam_Field_Serialized, array(), array()),
			array(new Jam_Field_Serialized, 'a:1:{i:0;s:4:"test";}', array('test')),
			array(new Jam_Field_Serialized, 's:0:"";', ''),

			// Timestamps
			array(new Jam_Field_Timestamp, 'Some Unparseable Time', 'Some Unparseable Time'),
			array(new Jam_Field_Timestamp, '1264985682', '1264985682'),
			array(new Jam_Field_Timestamp, '03/15/2010 12:56:32', '03/15/2010 12:56:32'),

			// Weblink
			array(new Jam_Field_Weblink, 'example.com', 'http://example.com'),
			array(new Jam_Field_Weblink, 'http://example.com', 'http://example.com'),
			array(new Jam_Field_Weblink, 'www.example.com', 'http://www.example.com'),
			array(new Jam_Field_Weblink, '', ''),
			array(new Jam_Field_Weblink, 'abc.e-fsdf-43xample.com', 'http://abc.e-fsdf-43xample.com'),
			array(new Jam_Field_Weblink, 'https://example.com', 'https://example.com'),
		);
	}

	/**
	 * Tests Jam_Field::set
	 *
	 * @dataProvider provider_set
	 */
	public function test_set($field, $value, $expected)
	{
		$model = Jam::build('test_position');
		$this->assertSame($expected, $field->set($model, $value, TRUE));
	}

	public static function filter_test($model, $value, $field)
	{
		return get_class($model).':'.$value.':'.$field;
	}

	public function data_filters()
	{
		return array(
			array(new Jam_Field_String(array('filters' => array('trim'))), '  text ', 'text'),
			array(new Jam_Field_Integer(array('filters' => array('trim'))), '  12 ', 12),
			array(new Jam_Field_Text(array('filters' => array('Jam_FieldTest::filter_test' => array(':model', ':value', ':field')))), 'value12', 'Model_Test_Position:value12:myfield'),
		);
	}

	/**
	 * @dataProvider data_filters
	 */
	public function test_filters($field, $value, $expected)
	{
		$model = Jam::build('test_position');
		$field->name = 'myfield';
		$this->assertEquals($expected, $field->set($model, $value, TRUE));
	}

}
