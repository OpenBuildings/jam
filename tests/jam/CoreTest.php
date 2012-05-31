<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for core Jam methods.
 *
 * @package Jam
 * @group   jam
 * @group   jam.core
 */
class Jam_CoreTest extends Unittest_TestCase {

	/**
	 * Provides for test_register
	 */
	public function provider_register()
	{
		return array(
			array('test_alias', TRUE),
			array(new Model_Test_Alias, TRUE),
			
			// Model_Invalid exists but does not extend Jam_Model
			array('test_invalid', FALSE),
			
			// Model_Unknown does not exist
			array('test_unknown', FALSE),
			
			// Shouldn't throw any exceptions
			array(NULL, FALSE),
		);
	}

	/**
	 * Tests Jam::register()
	 * 
	 * @dataProvider provider_register
	 */
	public function test_register($model, $expected)
	{
		$this->assertSame(Jam::register($model), $expected);
	}
	
	/**
	 * Tests Jam::meta() and that meta objects are correctly returned.
	 * 
	 * @dataProvider provider_register
	 */
	public function test_meta($model, $expected)
	{
		$result = Jam::meta($model);
		
		// Should return a Jam_Meta instance
		if ($expected === TRUE)
		{
			$this->assertTrue($result instanceof Jam_Meta);
			$this->assertTrue($result->initialized());
		}
		else
		{
			$this->assertFalse($result);
		}
	}
	
	/**
	 * Provider for test_model_name
	 */
	public function provider_model_name()
	{
		return array(
			array('model_test_alias', 'test_alias'),
			array(new Model_Test_Alias, 'test_alias'),
			array('test_alias', 'test_alias'), // Should not chomp if there is no prefix
		);
	}
	
	/**
	 * Tests Jam::model_name().
	 * 
	 * @dataProvider provider_model_name
	 */
	public function test_model_name($model, $expected)
	{
		$this->assertSame($expected, Jam::model_name($model));
	}
	
	/**
	 * Provider for test_class_name
	 */
	public function provider_class_name()
	{
		return array(
			array('test_alias', 'model_test_alias'),
			array(new Model_Test_Alias, 'model_test_alias'),
			array('model_test_alias', 'model_model_test_alias'), // Should add prefix even if it already exists
		);
	}
	
	/**
	 * Tests Jam::class_name()
	 * 
	 * @dataProvider provider_class_name
	 */
	public function test_class_name($model, $expected)
	{
		$this->assertSame($expected, Jam::class_name($model));
	}
}