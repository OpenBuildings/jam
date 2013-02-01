<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.insert
 */
class Jam_Query_Builder_InsertTest extends Unittest_TestCase {

	public function test_constructor()
	{
		$insert = Jam_Query_Builder_Insert::factory('test_author');

		$insert
			->columns(array('name'));

		$insert
			->values(array('Test'));

		$this->assertInstanceOf('Jam_Query_Builder_Insert', $insert);

		$this->assertEquals(Jam::meta('test_author'), $insert->meta());

		$this->assertEquals('INSERT INTO test_authors (name) VALUES (\'Test\')', $insert->compile());
	}
}
	