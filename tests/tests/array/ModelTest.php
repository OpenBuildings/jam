<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests jam array.
 *
 * @package Jam
 * @group   jam
 * @group   jam.array
 * @group   jam.array.model
 */
class Jam_Array_ModelTest extends PHPUnit_Framework_TestCase {

	public $data = array(array('id' => 1, 'name' => 'one'), array('id' => 3, 'name' => 'three'));
	public $collection;
	public $array;

	public function setUp()
	{
		parent::setUp();

		$this->collection = new Jam_Query_Builder_Collection('test_element');
		$this->collection->load_fields($this->data);

		$this->array = new Jam_Array_Model();
		$this->array->collection($this->collection);
		$this->array->model('test_element');
	}

	public function test_convert_collection_to_array()
	{
		$model1 = Jam::build('test_element')->load_fields($this->data[0]);
		$model2 = Jam::build('test_element')->load_fields($this->data[1]);

		$array = array($model1, $model2);

		$converted1 = Jam_Array_Model::convert_collection_to_array($this->data);
		$this->assertEquals($this->data, $converted1);

		$converted2 = Jam_Array_Model::convert_collection_to_array($array);
		$this->assertEquals($array, $converted2);

		$converted3 = Jam_Array_Model::convert_collection_to_array($model1);
		$this->assertEquals(array($model1), $converted3);

		$converted4 = Jam_Array_Model::convert_collection_to_array($this->collection);
		$this->assertInternalType('array', $converted4);

		$this->assertEquals($array, $converted4);
	}

	public function test_load_content()
	{
		$this->assertEquals($this->data, $this->array->content());
	}

	public function test_search_and_has()
	{
		$model1 = Jam::build('test_element')->load_fields(array('id' => 1, 'name' => 'one'));
		$model2 = Jam::build('test_element', array('id' => 3));
		$model3 = Jam::build('test_element')->load_fields(array('id' => 4, 'name' => 'one'));
		$model4 = array('id' => 1);
		$model5 = array('id' => 10);

		$this->assertEquals(0, $this->array->search($model1));
		$this->assertTrue($this->array->has($model1));
		$this->assertEquals(1, $this->array->search($model2));
		$this->assertTrue($this->array->has($model2));
		$this->assertEquals(NULL, $this->array->search($model3));
		$this->assertFalse($this->array->has($model3));
		$this->assertEquals(0, $this->array->search($model4));
		$this->assertTrue($this->array->has($model4));
		$this->assertEquals(NULL, $this->array->search($model5));
		$this->assertFalse($this->array->has($model3));
	}

	public function test_ids_original_ids()
	{
		$this->assertEquals(array(1, 3), $this->array->ids());
		$this->assertEquals(array(1, 3), $this->array->original_ids());

		$this->array[] = Jam::build('test_element')->load_fields(array('id' => 4, 'name' => 'one'));

		$this->assertEquals(array(1, 3, 4), $this->array->ids());
		$this->assertEquals(array(1, 3), $this->array->original_ids());
	}

	public function test_set()
	{
		$this->assertFalse($this->array->changed());

		$model1 = Jam::build('test_element')->load_fields(array('id' => 2, 'name' => 'two'));

		$this->array->set(array(array(), array('id' => 2, 'name' => 'two'), $model1));

		$this->assertTrue($this->array->changed());
		$this->assertEquals(array(array('id' => 2, 'name' => 'two'), $model1), $this->array->content());

		$this->assertTrue($this->array->changed(0));
		$this->assertTrue($this->array->changed(1));
		$this->assertSame($model1, $this->array[1]);

		$this->array->set(array());
		$this->assertTrue($this->array->changed());
	}

	public function test_changed()
	{
		$this->assertFalse($this->array->changed());
		$this->array[0]->name = 'two';
		$this->array[1]->id = 20;
		$this->assertTrue($this->array->changed(0));
		$this->assertTrue($this->array->changed(1));
		$this->assertTrue($this->array->changed());
	}

	public function test_add()
	{
		$model1 = Jam::build('test_element')->load_fields(array('id' => 2, 'name' => 'two'));
		$model2 = Jam::build('test_element', array('id' => 5));
		$model3 = Jam::build('test_element')->load_fields(array('id' => 1, 'name' => 'one'));

		$array1 = array(
			Jam::build('test_element')->load_fields(array('id' => 6, 'name' => 'more')),
			Jam::build('test_element')->load_fields(array('id' => 7, 'name' => 'more')),
		);
		$collection1 = new Jam_Query_Builder_Collection('test_element');
		$collection1->load_fields(array(
			array('id' => 8, 'name' => 'collection'),
			array('id' => 9, 'name' => 'collection'),
		));

		$this->array->add($model1);
		$this->array->add($model2);
		$this->array->add($model3);
		$this->array->add($array1);
		$this->array->add($collection1);

		$this->assertTrue($this->array->has($model1));
		$this->assertTrue($this->array->has($model2));
		$this->assertTrue($this->array->has($model3));
		$this->assertTrue($this->array->has($array1[0]));
		$this->assertTrue($this->array->has($array1[1]));
		$this->assertTrue($this->array->has($collection1[0]));
		$this->assertTrue($this->array->has($collection1[1]));

		$this->assertSame($model1, $this->array[2]);
		$this->assertSame($model2, $this->array[3]);
		$this->assertSame($model3, $this->array[0]);
		$this->assertSame($array1[0], $this->array[4]);
		$this->assertSame($array1[1], $this->array[5]);
		$this->assertEquals($collection1[0], $this->array[6]);
		$this->assertEquals($collection1[1], $this->array[7]);
	}

	public function data_remove()
	{
		$collection1 = new Jam_Query_Builder_Collection('test_element');
		$collection1->load_fields(array(
			array('id' => 1, 'name' => 'one'),
			array('id' => 3, 'name' => 'three'),
		));

		return array(
			array(Jam::build('test_element')->load_fields(array('id' => 1, 'name' => 'one')), array(3 => 'three')),
			array(Jam::build('test_element')->load_fields(array('id' => 6, 'name' => 'one')), array(1 => 'one', 3 => 'three')),
			array(3, array(1 => 'one')),
			array(array('id' => 3), array(1 => 'one')),
			array($collection1, array()),
			array(
				array(
					Jam::build('test_element')->load_fields(array('id' => 3, 'name' => 'three')), 
					Jam::build('test_element')->load_fields(array('id' => 1, 'name' => 'one'))
				), 
				array()
			),
		);
	}

	/**
	 * @dataProvider data_remove
	 */
	public function test_remove($remove, $expected)
	{
		$this->array->remove($remove);

		$this->assertEquals($expected, $this->array->as_array('id', 'name'));
	}

	public function test_build()
	{
		$this->array->build(array('name' => 'test'));

		$this->assertInstanceOf('Model_Test_Element', $this->array[2]);
		$this->assertEquals('test', $this->array[2]->name);
	}

	public function test_check_changed()
	{
		$model = $this->getMock('Model_Test_Element', array('check'), array('test_element'));
		$model
			->expects($this->at(0))
			->method('check')
			->will($this->returnValue(TRUE));

		$model
			->expects($this->at(1))
			->method('check')
			->will($this->returnValue(FALSE));

		$this->array->add($model);

		$this->assertTrue($this->array->check_changed());
		$this->assertFalse($this->array->check_changed());
	}


	public function test_save_changed()
	{
		$model = $this->getMock('Model_Test_Element', array('save', 'is_saving'), array('test_element'));
		$model
			->expects($this->once())
			->method('save');

		$model
			->expects($this->at(0))
			->method('is_saving')
			->will($this->returnValue(TRUE));

		$model
			->expects($this->at(1))
			->method('is_saving')
			->will($this->returnValue(FALSE));

		$this->array->add($model);

		$this->array->save_changed();
		$this->array->save_changed();
	}

	public function test_query_builder()
	{
		$expected_sql = 'SELECT `test_elements`.* FROM `test_elements` WHERE `test_elements`.`name` = \'two\'';
		$this->assertEquals($expected_sql, (string) $this->array->where('name', '=', 'two'));

		$expected_sql = 'SELECT `test_elements`.* FROM `test_elements` WHERE `test_elements`.`id` = 2 OR `test_elements`.`name` = \'one\'';
		$this->assertEquals($expected_sql, (string) $this->array->where('id', '=', 2)->or_where('name', '=', 'one'));
	}

	public function test_null_content()
	{
		$association_array = Jam_Array_Association::factory();
		$association_array->model('test_blog');
		$this->assertSame(array(), $association_array->as_array());
	}
}