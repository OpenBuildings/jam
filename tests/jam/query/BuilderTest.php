<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 */
class Jam_Query_BuilderTest extends Unittest_Jam_TestCase {

	public function data_resolve_attribute_name()
	{
		return array(
			array('test_author.name', 'test_authors.name'),
			array('email', 'test_authors.email'),
			array(':primary_key', 'test_authors.id'),
			array(':name_key', 'test_authors.name'),
			array('test_author.:primary_key', 'test_authors.id'),
			array('test_author.:name_key', 'test_authors.name'),
			array('test_author.:foreign_key', 'test_authors.test_author_id'),
			array('test_blog.test_author:foreign_key', 'test_blogs.test_author_id'),
		);
	}

	/**
	 * @dataProvider data_resolve_attribute_name
	 */
	public function test_resolve_attribute_name($name, $expected)
	{
		$meta = Jam::meta('test_author');

		$this->assertEquals($expected, Jam_Query_Builder::resolve_attribute_name($name, $meta));
	}
}