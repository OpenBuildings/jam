<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests jam array.
 *
 * @package Jam
 * @group   jam
 * @group   jam.array
 * @group   jam.array.association
 */
class Jam_Array_AssociationTest extends PHPUnit_Framework_TestCase {

	public $data = array(array('id' => 1, 'name' => 'one'), array('id' => 3, 'name' => 'three'));
	public $collection;
	public $array;
	public $association;
	public $parent;

	public function setUp()
	{
		parent::setUp();

		$this->collection = new Jam_Query_Builder_Collection('test_element');
		$this->collection->load_fields($this->data);

		$this->parent = Jam::build('test_author')->load_fields(array('id' => 1, 'name' => 'author'));
		$this->association = $this->getMock('Jam_Association_Hasmany', array('item_get', 'item_set', 'item_unset', 'clear', 'save', 'collection'));
		$this->association->initialize(Jam::meta('test_author'), 'test_elements');

		$this->array = new Jam_Array_Association();
		$this->array
			->model('test_element')
			->association($this->association)
			->parent($this->parent);
	}

	public function test_load_content()
	{
		$this->association
			->expects($this->once())
			->method('collection')
			->will($this->returnValue($this->collection));

		$this->assertEquals($this->data, $this->array->content());
	}

	public function test_not_loaded()
	{
		$this->array->collection($this->collection);

		$this->array->parent(Jam::build('test_author'));

		$this->assertNull($this->array->content());

		$model = Jam::build('test_element')->load_fields(array('id' => 4, 'name' => 'four'));

		$this->array->add($model);

		$this->assertTrue($this->array->has($model));
		$this->array->parent($this->parent);

		$this->assertTrue($this->array->has($model));
		$this->assertEquals(array(4 => 'four', 1 => 'one', 3 => 'three'), $this->array->as_array('id', 'name'));
	}

	public function test_clear()
	{
		$this->association
			->expects($this->once())
			->method('clear');

		$this->array->clear();
	}


	public function test_save()
	{
		$this->association
			->expects($this->once())
			->method('save');

		$this->array->save();
	}

	public function test_offsetGet()
	{
		$this->array->collection($this->collection);

		$this->association
			->expects($this->once())
			->method('item_get')
			->with($this->parent, $this->collection[0]);

		$this->array[0];
	}

	public function test_offfsetSet()
	{
		$this->array->collection($this->collection);

		$this->association
			->expects($this->once())
			->method('item_set')
			->with($this->parent, $this->collection[0]);

		$this->array[0] = $this->collection[0];
	}

	public function test_offfsetUnset()
	{
		$this->array->collection($this->collection);

		$this->association
			->expects($this->once())
			->method('item_unset')
			->with($this->parent, $this->collection[0]);

		unset($this->array[0]);
	}
}