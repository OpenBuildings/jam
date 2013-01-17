<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.dynamic
 */
class Jam_Query_Builder_DynamicTest extends Unittest_TestCase {

	public $result;
	public $collection;
	public $data;

	public function setUp()
	{
		parent::setUp();

		$this->data = array(
			array('id' => 1, 'name' => 'Staff'),
			array('id' => 2, 'name' => 'Freelancer'),
			array('id' => 3, 'name' => 'Manager'),
		);

		$this->result = new Jam_Query_Builder_Dynamic_Result($this->data, '', FALSE);

		$this->collection = $this->getMock('Jam_Query_Builder_Dynamic', array('_find_item'), array('test_position'));
		$this->collection->result($this->result);
	}

	public function test_load_fields()
	{
		$collection = new Jam_Query_Builder_Dynamic('test_position', $this->data);

		foreach ($collection as $i => $item) 
		{
			$this->assertTrue($item->loaded());
			$this->assertEquals($this->data[$i], $item->as_array());
		}

		$data = array(
			array('id' => 1, 'name' => 'post 1'),
			array('id' => 2, 'name' => 'post 2'),
			array('id' => 3, 'name' => 'post 3', 'test_author' => array('id' => 2, 'name' => 'Author 2')),
		);

		$collection = new Jam_Query_Builder_Dynamic('test_post');
		$collection->load_fields($data);

		foreach ($collection as $i => $item) 
		{
			$this->assertTrue($item->loaded());
			$this->assertEquals($data[$i]['id'], $item->id());
			$this->assertEquals($data[$i]['name'], $item->name());
		}

		$author = $collection[2]->test_author;
		$this->assertTrue($author->loaded());
		$this->assertEquals($author->name(), 'Author 2');
	}

	public function test_offsetSet()
	{
		$this->assertFalse(isset($this->collection[3]));
		$this->assertFalse($this->collection->changed());

		$this->collection[] = array('id' => 4, 'name' => 'Cleaner');
		$this->assertTrue(isset($this->collection[3]));

		$model = Jam::build('test_position');

		$this->collection
			->expects($this->once())
			->method('_find_item')
			->with($this->equalTo(4))
			->will($this->returnValue($model));

		$converted = $this->collection[3];
		$this->assertSame($model, $converted);
		$this->assertEquals('Cleaner', $converted->name);

		$this->assertCount(count($this->data) + 1, $this->collection);

		$additional = Jam::build('test_position')->load_fields(array('id' => 8, 'name' => 'Additional'));

		$this->collection[] = $additional;

		$this->assertSame($additional, $this->collection[4]);
		$this->assertTrue($this->collection->changed());
	}

	public function test_offsetGet()
	{
		$this->assertSame($this->collection[0], $this->collection[0], 'Should load the model only once, and then reuse it');

		$model = Jam::build('test_position');
		$this->collection->result(new Jam_Query_Builder_Dynamic_Result(array_merge($this->data, array(5, $model)), '', FALSE));

		$this->assertInstanceOf('Model_Test_Position', $this->collection[0]);
		$this->assertEquals($this->data[0], $this->collection[0]->as_array());

		$this->assertSame($model, $this->collection[4]);

		$this->collection
			->expects($this->at(0))
			->method('_find_item')
			->with($this->equalTo(5))
			->will($this->returnValue($model));

		$this->assertSame($model, $this->collection[3]);
	}

	public function test_offsetUnset()
	{
		$this->assertTrue(isset($this->collection[2]));
		$this->assertFalse($this->collection->changed());

		unset($this->collection[2]);

		$this->assertFalse(isset($this->collection[2]));
		$this->assertTrue($this->collection->changed());

		$this->assertCount(2, $this->collection);
	}

	public function test_search()
	{
		$this->collection->result($this->result);

		$additional = Jam::build('test_position')->load_fields(array('id' => 8, 'name' => 'Additional'));

		$this->collection
			->expects($this->once())
			->method('_find_item')
			->with($this->equalTo('Additional'))
			->will($this->returnValue($additional));

		$this->collection[] = $additional;

		$this->assertEquals(1, $this->collection->search(2), 'Search for model with id 2');
		$this->assertEquals(3, $this->collection->search(8), 'Search for model with id 8 (additional)');
		$this->assertEquals(3, $this->collection->search($additional), 'Search for model with the object additional');
		$this->assertEquals(3, $this->collection->search(array('id' => 8)), 'Search for model with id 8 (additional)');
		$this->assertEquals(3, $this->collection->search('Additional'), 'Search for model with name key Additional (additional)');
	}

	public function test_ids()
	{
		$additional = Jam::build('test_position')->load_fields(array('id' => 8, 'name' => 'Additional 1'));
		$this->collection->add($additional);
		$this->collection->add(20);

		$this->assertEquals(array(1, 2, 3, 8, 20), $this->collection->ids());
	}

	public function test_has()
	{
		$additional = Jam::build('test_position')->load_fields(array('id' => 8, 'name' => 'Additional'));
		$additional_not_included = Jam::build('test_position')->load_fields(array('id' => 12, 'name' => 'Additional'));

		$this->collection
			->expects($this->once())
			->method('_find_item')
			->with($this->equalTo('Additional'))
			->will($this->returnValue($additional));

		$this->collection[] = $additional;
		$this->collection[] = 9;

		$this->assertTrue($this->collection->has(2));
		$this->assertTrue($this->collection->has(8));
		$this->assertTrue($this->collection->has(9));
		$this->assertTrue($this->collection->has('Additional'));
		$this->assertTrue($this->collection->has(array('id' => 8)));
		$this->assertTrue($this->collection->has(array('id' => 9)));
		$this->assertTrue($this->collection->has($additional));
		$this->assertFalse($this->collection->has(array('id' => 10)));
		$this->assertFalse($this->collection->has(11));
		$this->assertFalse($this->collection->has($additional_not_included));
	}

	public function test_as_array()
	{
		// Test with no keys or values
		$array = $this->collection->as_array();
		$this->assertSame($this->collection[0], $array[0]);
		$this->assertInternalType('array', $array);

		foreach ($array as $offset => $model) 
		{
			$this->assertInstanceOf('Model_Test_Position', $model);
			$this->assertEquals($this->data[$offset], $model->as_array());
		}

		// Test with keys
		$array = $this->collection->as_array('name');
		$this->assertInternalType('array', $array);

		$offset = 0;
		foreach ($array as $name => $model) 
		{
			$this->assertInstanceOf('Model_Test_Position', $model);
			$this->assertEquals($this->data[$offset]['name'], $name);
			$this->assertSame($this->collection[$offset], $model);

			$offset += 1;
		}

		// Test with keys as meta aliases
		$array = $this->collection->as_array(':primary_key');
		$this->assertInternalType('array', $array);

		$offset = 0;
		foreach ($array as $name => $model) 
		{
			$this->assertInstanceOf('Jam_Model', $model);
			$this->assertEquals($this->data[$offset]['id'], $name);
			$this->assertSame($this->collection[$offset], $model);

			$offset += 1;
		}

		// Test with values
		$array = $this->collection->as_array(NULL, 'name');
		$this->assertEquals(Arr::pluck($this->data, 'name'), $array);

		// Test with values as aliases
		$array = $this->collection->as_array(NULL, ':primary_key');
		$this->assertEquals(Arr::pluck($this->data, 'id'), $array);

		// Test with keys and values as aliases
		$array = $this->collection->as_array(':name_key', ':primary_key');
		$this->assertEquals(array_combine(Arr::pluck($this->data, 'name'), Arr::pluck($this->data, 'id')), $array);

	}

	public function data_add()
	{
		$additional1 = Jam::build('test_position')->load_fields(array('id' => 8, 'name' => 'Additional 1'));
		$additional2 = Jam::build('test_position')->load_fields(array('id' => 12, 'name' => 'Additional 2'));
		
		$additional_collection = Jam_Query_Builder_Dynamic::factory('test_position')
			->set(array($additional1, $additional2));

		return array(
			array(10, array(1, 2, 3, 10)),
			array(array(10, 15), array(1, 2, 3, 10, 15)),
			array(array(array('id' => 5), array('id' => 7)), array(1, 2, 3, 5, 7)),
			array($additional1, array(1, 2, 3, 8)),
			array(array($additional1), array(1, 2, 3, 8)),
			array(array($additional1, $additional2), array(1, 2, 3, 8, 12)),
			array($additional_collection, array(1, 2, 3, 8, 12)),
		);
	}

	/**
	 * @dataProvider data_add
	 */
	public function test_add($items, $expected_ids)
	{
		$this->assertEquals($expected_ids, $this->collection->add($items)->ids());
		$this->assertEquals($expected_ids, $this->collection->add($items)->ids(), 'Must be the same ids when adding duplicates');
	}

	public function data_remove()
	{
		$current2 = Jam::build('test_position')->load_fields(array('id' => 2, 'name' => 'Freelancer'));
		$current3 = Jam::build('test_position')->load_fields(array('id' => 3, 'name' => 'Manager'));
		
		$current_collection = Jam_Query_Builder_Dynamic::factory('test_position')
			->set(array($current2, $current3));

		return array(
			array(3, array(1, 2)),
			array(array(1, 3), array(2)),
			array(array(array('id' => 1), array('id' => 2)), array(3)),
			array($current2, array(1, 3)),
			array(array($current3), array(1, 2)),
			array(array($current2, $current3), array(1)),
			array($current_collection, array(1)),
		);
	}

	/**
	 * @dataProvider data_remove
	 */
	public function test_remove($items, $expected_ids)
	{
		$this->assertEquals($expected_ids, $this->collection->remove($items)->ids());
		$this->assertEquals($expected_ids, $this->collection->remove($items)->ids(), 'Should not remove anything twice');
	}

	public function data_set()
	{
		$current2 = Jam::build('test_position')->load_fields(array('id' => 2, 'name' => 'Freelancer'));
		$current3 = Jam::build('test_position')->load_fields(array('id' => 3, 'name' => 'Manager'));
		
		$current_collection = Jam_Query_Builder_Dynamic::factory('test_position')
			->set(array($current2, $current3));

		return array(
			array(3, array(3)),
			array(array(1, 3), array(1, 3)),
			array(array(array('id' => 1), array('id' => 2)), array(1, 2)),
			array($current2, array(2)),
			array(array($current3), array(3)),
			array(array($current2, $current3), array(2, 3)),
			array($current_collection, array(2, 3)),
		);
	}

	/**
	 * @dataProvider data_set
	 */	
	public function test_set($items, $expected_ids)
	{
		$this->assertFalse($this->collection->changed());
		$this->assertEquals($expected_ids, $this->collection->set($items)->ids());
		$this->assertTrue($this->collection->changed());
	}

}