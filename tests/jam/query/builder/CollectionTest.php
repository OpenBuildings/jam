<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.collection
 */
class Jam_Query_Builder_CollectionTest extends Unittest_Jam_TestCase {

	public function test_count()
	{
		$collection = new Jam_Query_Builder_Collection('test_author');

		$this->assertEquals(3, count($collection));
	}

	public function test_offsetGet()
	{
		$collection = new Jam_Query_Builder_Collection('test_author');	

		$collection->offsetGet(0);
		
	}
}