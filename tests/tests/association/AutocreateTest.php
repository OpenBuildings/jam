<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests Belongsto associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.autocreate
 */
class Jam_Association_AutocreateTest extends Testcase_Database {

	public $meta;

	public function setUp()
	{
		parent::setUp();

		$this->meta = $this->getMock('Jam_Meta', array('field'), array('test_post'));
	}

	public function data_set()
	{
		return array(
			array(NULL, NULL, NULL, NULL),
			array(1, NULL, 1, 1),
			array(
				'test',
				NULL,
				array('id' => ':last_insert_id', 'name' => 'test', 'password' => NULL, 'email' => '', 'test_position_id' => NULL),
				':last_insert_id',
			),
			array(
				'test',
				array('email' => 'my email'),
				array('id' => ':last_insert_id', 'name' => 'test', 'password' => NULL, 'email' => 'my email', 'test_position_id' => NULL),
				':last_insert_id',
			),
		);
	}

	/**
	 * @dataProvider data_set
	 * @covers Jam_Association_Autocreate::set
	 */
	public function test_set($value, $default_fields, $expected_value, $expected_foreign_key)
	{
		$association = new Jam_Association_Autocreate(array('default_fields' => $default_fields));
		$association->initialize($this->meta, 'test_author');

		$model = new Model_Test_Post();
		$value = $association->set($model, $value, TRUE);

		$last_insert_id = DB::select(array(DB::expr('LAST_INSERT_ID()'), 'id'))
			->from('test_authors')
			->execute(Kohana::TESTING)
			->get('id');

		if ($expected_foreign_key == ':last_insert_id')
		{
			$expected_foreign_key = $last_insert_id;
		}

		if (is_array($expected_value))
		{
			if ($expected_value['id'] == ':last_insert_id')
			{
				$expected_value['id'] = $last_insert_id;
			}

			$this->assertEquals($expected_value, $value->as_array());
		}
		else
		{
			$this->assertEquals($expected_value, $value);
		}

		$this->assertEquals($expected_foreign_key, $model->{$association->foreign_key}, 'Should have correct value for column '.$association->foreign_key);
	}
}
