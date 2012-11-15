<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.join
 */
class Jam_Query_Builder_JoinTest extends Unittest_Jam_TestCase {

	public function test_compile()
	{
		$join = new Jam_Query_Builder_Join('test_author');

		$this->assertEquals(Jam::meta('test_author'), $join->meta());

		$join->on(':primary_key', '=', 'test_blog.test_author:foreign_key');

		$this->assertEquals('JOIN `test_authors` ON (`test_authors`.`id` = `test_blogs`.`test_author_id`)', (string) $join);
	}

	public function test_compile_not_model()
	{
		$join = new Jam_Query_Builder_Join('test_authors');

		$this->assertNull($join->meta());

		$this->assertEquals('JOIN `test_authors` ON ()', (string) $join);
	}

	public function test_compile_alias()
	{
		$join = new Jam_Query_Builder_Join(array('test_authors', 'authors'));

		$this->assertNull($join->meta());

		$this->assertEquals('JOIN `test_authors` AS `authors` ON ()', (string) $join);
	}

	public function test_type()
	{
		$join = new Jam_Query_Builder_Join('test_author', 'RIGHT');

		$this->assertEquals('RIGHT JOIN `test_authors` ON ()', (string) $join);
	}

	public function test_context()
	{
		$author_meta = Jam::meta('test_author');
		$join = new Jam_Query_Builder_Join('test_post', NULL);
		$join->context($author_meta);

		$this->assertEquals('JOIN `test_posts` ON (`test_author`.`id` = `test_posts`.`test_author_id`)', (string) $join);	
	}
}