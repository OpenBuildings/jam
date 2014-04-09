<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for core Jam methods.
 *
 * @package Jam
 * @group   jam
 * @group   jam.core
 */
class Jam_CoreTest extends PHPUnit_Framework_TestCase {

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
			array('test_alias', 'Model_Test_Alias'),
			array(new Model_Test_Alias, 'Model_Test_Alias'),
			array('model_test_alias', 'Model_Model_Test_Alias'), // Should add prefix even if it already exists
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

	public function test_collection_class()
	{
		$this->assertInstanceOf('Model_Collection_Test_Author', Jam::all('test_author'));
		$collection = Jam::all('test_author')->where_author(1);
		$this->assertCount(1, $collection->where);
		$this->assertInstanceOf('Jam_Query_Builder_Collection', Jam::all('test_blog'));
	}

	public function test_build()
	{
		$model = Jam::build('test_author');
		$this->assertInstanceOf('Model_Test_Author', $model);
		$this->assertFalse($model->loaded());

		$model = Jam::build('test_position');
		$this->assertInstanceOf('Model_Test_Position', $model);

		$model = Jam::build('test_position', array('model' => 'test_position_big'));
		$this->assertInstanceOf('Model_Test_Position_Big', $model);
	}

	public function test_build_template()
	{
		$model = Jam::build_template('test_author');
		$this->assertInstanceOf('Model_Test_Author', $model);
		$this->assertFalse($model->loaded());

		$model2 = Jam::build_template('test_author');
		$this->assertSame($model, $model2);

		$model = Jam::build_template('test_position');
		$this->assertInstanceOf('Model_Test_Position', $model);

		$model = Jam::build_template('test_position', array('model' => 'test_position_big'));
		$this->assertInstanceOf('Model_Test_Position_Big', $model);
	}

	/**
	 * @covers Jam::insert
	 */
	public function test_insert()
	{
		$insert = Jam::insert('test_author');
		$this->assertInstanceOf('Jam_Query_Builder_Insert' ,$insert);
		$this->assertSame(Jam::meta('test_author'), $insert->meta());
		$this->assertEquals(
			'INSERT INTO `test_authors` () VALUES ',
			$insert->compile()
		);

		$insert = Jam::insert('test_author', array('name', 'email'));
		$this->assertEquals(
			'INSERT INTO `test_authors` (`name`, `email`) VALUES ',
			$insert->compile()
		);
	}
}
