<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.collection
 */
class Jam_Query_Builder_CollectionTest extends Unittest_TestCase {

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

		$this->result = new Database_Result_Cached($this->data, '', FALSE);

		$this->collection = new Jam_Query_Builder_Collection('test_position');
		$this->collection->result($this->result);
	}

	public function test_count()
	{
		$this->assertCount(count($this->data), $this->collection);
	}

	public function test_offsetGet()
	{
		$this->assertInstanceOf('Jam_Model', $this->collection->offsetGet(0));
		$this->assertEquals($this->data[0], $this->collection->offsetGet(0)->as_array());

		$this->assertInstanceOf('Jam_Model', $this->collection->offsetGet(1));
		$this->assertEquals($this->data[1], $this->collection->offsetGet(1)->as_array());

		$this->assertInstanceOf('Jam_Model', $this->collection->offsetGet(2));
		$this->assertEquals($this->data[2], $this->collection->offsetGet(2)->as_array());
		$this->assertNull($this->collection->offsetGet(3));
	}

	public function test_offsetExists()
	{
		$this->assertTrue($this->collection->offsetExists(0));
		$this->assertTrue($this->collection->offsetExists(1));
		$this->assertTrue($this->collection->offsetExists(2));
		$this->assertFalse($this->collection->offsetExists(3));
	}

	public function test_offsetSet()
	{
		$this->setExpectedException('Kohana_Exception', 'Database results are read-only');
		$this->collection->offsetSet(3, array('id' => 4, 'name' => 'Cleaner'));
	}

	public function test_offsetUnset()
	{
		$this->setExpectedException('Kohana_Exception', 'Database results are read-only');
		$this->collection->offsetUnset(0);
	}

	public function test_foreach()
	{
		foreach ($this->collection as $i => $model) 
		{
			$this->assertEquals($this->data[$i], $model);
		}
	}

	public function test_ids()
	{
		$this->assertEquals(Arr::pluck($this->data, 'id'), $this->collection->ids());
	}

	public function test_as_array()
	{
		// Test with no keys or values
		$array = $this->collection->as_array();
		$this->assertInternalType('array', $array);

		foreach ($array as $offset => $model) 
		{
			$this->assertInstanceOf('Jam_Model', $model);
			$this->assertEquals($this->data[$offset], $model->as_array());
		}

		// Test with keys
		$array = $this->collection->as_array('name');
		$this->assertInternalType('array', $array);

		$offset = 0;
		foreach ($array as $name => $model) 
		{
			$this->assertInstanceOf('Jam_Model', $model);
			$this->assertEquals($this->data[$offset]['name'], $name);
			$this->assertEquals($this->data[$offset], $model->as_array());

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
			$this->assertEquals($this->data[$offset], $model->as_array());

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
}