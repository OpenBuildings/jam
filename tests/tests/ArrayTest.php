<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests jam array.
 *
 * @package Jam
 * @group   jam
 * @group   jam.array
 * @group   jam.array.core
 */
class Jam_ArrayTest extends PHPUnit_Framework_TestCase {

	public function test_content_not_set()
	{
		$array = new Jam_Array();
		$this->setExpectedException('Kohana_Exception');
		$array->content();
	}

	public function test_array_access()
	{
		$data = array('one', 'two', 'three');
		$array = new Jam_Array();
		$array->content($data);

		$this->assertEquals($data, $array->content());

		$this->assertCount(3, $array);

		$this->assertEquals($data[0], $array[0]);
		$this->assertEquals($data[1], $array[1]);
		$this->assertEquals($data[2], $array[2]);
		$this->assertNull($array[3]);

		$array[] = 'new';

		$this->assertCount(4, $array);

		$this->assertEquals('new', $array[3]);

		$array[0] = 'changed';

		$this->assertCount(4, $array);

		$this->assertEquals('changed', $array[0]);
	}

	public function test_foreach()
	{
		$data = array('one', 'two', 'three');
		$array = new Jam_Array();
		$array->content($data);

		foreach ($array as $offset => $item)
		{
			$this->assertEquals($data[$offset], $item);
		}
	}

	public function test_changed()
	{
		$data = array('one', 'two', 'three');
		$array = new Jam_Array();
		$array->content($data);

		$this->assertFalse($array->changed());
		foreach ($data as $offset => $value)
		{
			$this->assertFalse($array->changed($offset));
		}

		$array[0] = 'changed';
		$array[] = 'new';

		$this->assertTrue($array->changed(0));
		$this->assertFalse($array->changed(1));
		$this->assertFalse($array->changed(2));
		$this->assertTrue($array->changed(3));
	}

	public function test_serialized()
	{
		$data = array('one', 'two', 'three');
		$array = new Jam_Array();
		$array->content($data);

		$array[0] = 'changed';
		$array[] = 'new';

		$serialized = serialize($array);

		$array = unserialize($serialized);

		$this->assertEquals(array('changed', 'two', 'three', 'new'), $array->content());

		$this->assertTrue($array->changed(0));
		$this->assertFalse($array->changed(1));
		$this->assertFalse($array->changed(2));
		$this->assertTrue($array->changed(3));
	}
}
