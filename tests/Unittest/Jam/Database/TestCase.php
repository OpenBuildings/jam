<?php defined('SYSPATH') OR die('No direct script access.');

class Unittest_Jam_Database_TestCase extends Unittest_Database_TestCase {

	static public $database_connection = 'jam-test';

	public function setUp()
	{
		$this->_database_connection = Unittest_Jam_Database_TestCase::$database_connection;
		parent::setUp();
	}

	/**
	 * Inserts default data into database.
	 *
	 * @return  PHPUnit_Extensions_Database_DataSet_IDataSet
	 * @uses    Kohana::find_file
	 */
	public function getDataSet()
	{
		return $this->createXMLDataSet(Kohana::find_file('tests/test_data/jam', 'test', 'xml'));
	}

	public function assertHasError($model, $attribute, $error)
	{
		$errors = $model->errors()->as_array();

		$this->assertArrayHasKey($error, (array) Arr::get($errors, $attribute), 'Should have error '.$error.' for '.$attribute);
	}
		
	public function assertNotHasError($model, $attribute, $error)
	{
		$errors = $model->errors()->as_array();

		$this->assertArrayNotHasKey($error, (array) Arr::get($errors, $attribute), 'Should not have error '.$error.' for '.$attribute);
	}

}