<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.join
 */
class Jam_Query_Builder_JoinTest extends PHPUnit_Framework_TestCase {

	public function test_compile()
	{
		$join = Jam_Query_Builder_Join::factory('test_author')
			->on(':primary_key', '=', 'test_blog.test_owner_id');

		$this->assertEquals('JOIN `test_authors` ON (`test_authors`.`id` = `test_blogs`.`test_owner_id`)', (string) $join);
	}

	public function test_compile_not_model()
	{
		$join = Jam_Query_Builder_Join::factory('test_authors');

		$this->assertEquals('JOIN `test_authors` ON ()', (string) $join);
	}

	public function test_compile_alias()
	{
		$join = Jam_Query_Builder_Join::factory(array('test_authors', 'authors'));

		$this->assertEquals('JOIN `test_authors` AS `authors` ON ()', (string) $join);
	}

	public function test_type()
	{
		$join = Jam_Query_Builder_Join::factory('test_author', 'RIGHT');

		$this->assertEquals('RIGHT JOIN `test_authors` ON ()', (string) $join);
	}

	public function test_context_model()
	{
		$join = Jam_Query_Builder_Join::factory('test_author')
			->on(':primary_key', '=', 'test_owner_id');

		$this->assertEquals('JOIN `test_authors` ON (`test_authors`.`id` = `test_owner_id`)', (string) $join);

		$join->context_model('test_blog');

		$this->assertEquals('JOIN `test_authors` ON (`test_authors`.`id` = `test_blogs`.`test_owner_id`)', (string) $join);
	}

	public function test_join_single()
	{
		$join = Jam_Query_Builder_Join::factory('test_post')
			->join('test_author');

		$this->assertEquals('JOIN `test_posts` ON () JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`)', (string) $join);
	}

	public function test_joins()
	{
		$join = Jam_Query_Builder_Join::factory('test_post')
			->join('test_blog')
			->join('test_author');

		$this->assertEquals('JOIN `test_posts` ON () JOIN `test_blogs` ON (`test_blogs`.`id` = `test_posts`.`test_blog_id`) JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`)', (string) $join);
	}

	public function test_nested_joins()
	{
		$join = Jam_Query_Builder_Join::factory('test_post')
			->join_nested('test_blog')
				->join('test_posts')
			->end();

		$this->assertEquals('JOIN `test_posts` ON () JOIN `test_blogs` ON (`test_blogs`.`id` = `test_posts`.`test_blog_id`) JOIN `test_posts` ON (`test_posts`.`test_blog_id` = `test_blogs`.`id`)', (string) $join);

	}

}
