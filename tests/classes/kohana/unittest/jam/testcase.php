<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Unittest_Jam_TestCase extends Unittest_Database_TestCase {

	static public $database_connection = 30;
	static public $database_type = 'mysql';

	public function setUp()
	{
		$this->_database_connection = Unittest_Jam_TestCase::$database_connection;
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

	protected function _builder_for_model($model, $key = NULL)
	{
		if ($model instanceof Jam_Model)
		{
			return Jam::query($model->meta()->model())->key($model->id());
		}
		elseif ($model instanceof Jam_Builder)
		{
			return $model;
		}
		else
		{
			return Jam::query($model)->key($key);
		}
	}

	public function assertExists($model, $key = NULL)
	{
		$builder = $this->_builder_for_model($model, $key);
		$this->assertTrue($builder->count() > 0, "The model ".$builder->meta()->model()." should exist in the database");
	}

	public function assertNotExists($model, $key = NULL)
	{
		$builder = $this->_builder_for_model($model, $key);
		$this->assertTrue($builder->count() == 0, "The model ".$builder->meta()->model()." should not exist in the database");
	}

} // End Kohana_Unittest_Jam_TestCase