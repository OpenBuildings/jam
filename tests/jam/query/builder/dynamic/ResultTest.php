<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.dynamic
 * @group   jam.query.builder.dynamic.result
 */
class Jam_Query_Builder_ResultTest extends Unittest_TestCase {

	public $data;

	public function setUp()
	{
		parent::setUp();

		$this->data = array(
			array('id' => 1, 'name' => 'Staff'),
			array('id' => 2, 'name' => 'Freelancer'),
			array('id' => 3, 'name' => 'Manager'),
		);
	}

	public function test_offsetSet()
	{
		$result = new Jam_Query_Builder_Dynamic_Result($this->data, NULL, FALSE);
		$result->force_offsetSet(NULL, array('id' => 4, 'name' => 'Addition'));

		$this->assertEquals(array('id' => 4, 'name' => 'Addition'), $result->offsetGet(3));

		$object1 = Jam::factory('test_position')->load_fields(array('id' => 4, 'name' => 'Additional 1'));
		$object2 = Jam::factory('test_position')->load_fields(array('id' => 5, 'name' => 'Additional 1'));

		$result->force_offsetSet(NULL, $object1);

		$this->assertSame($object1, $result->offsetGet(4));

		$result->force_offsetSet(2, $object2);

		$this->assertSame($object2, $result->offsetGet(2));

		$this->setExpectedException('Kohana_Exception', 'Must be a valid offset');
		$result->force_offsetSet(8, array('id' => 4, 'name' => 'Cleaner'));
	}

	public function test_offsetUnset()
	{
		$result = new Jam_Query_Builder_Dynamic_Result($this->data, NULL, FALSE);
		$result->force_offsetUnset(1);

		$this->assertEquals(array(array('id' => 1, 'name' => 'Staff'), array('id' => 3, 'name' => 'Manager')), $result->as_array());
	}

	public function test_as_array()
	{
		$result = new Jam_Query_Builder_Dynamic_Result($this->data, NULL, FALSE);

		$object = Jam::factory('test_position');
		$result->force_offsetSet(NULL, $object);

		$this->assertEquals(array_merge($this->data, array($object)), $result->as_array());
	}
}