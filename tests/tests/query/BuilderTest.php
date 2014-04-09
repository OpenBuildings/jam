<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.core
 */
class Jam_Query_BuilderTest extends PHPUnit_Framework_TestCase {

	public function data_resolve_attribute_name()
	{
		return array(
			array('test_author.name', 'test_author', 'test_authors.name'),
			array('email', 'test_author', 'test_authors.email'),
			array(':primary_key', 'test_author', 'test_authors.id'),
			array(':name_key', 'test_author', 'test_authors.name'),
			array('test_author.:primary_key', 'test_author', 'test_authors.id'),
			array('test_author.:name_key', 'test_author', 'test_authors.name'),
			array('email', NULL, 'email'),
			array('email', array('test_author', 'author'), 'author.email'),
			array(':name_key', array('test_author', 'author'), 'author.name'),
			array('test_post.:name_key', array('test_author', 'author'), 'test_posts.name'),
		);
	}

	/**
	 * @dataProvider data_resolve_attribute_name
	 */
	public function test_resolve_attribute_name($name, $context_model, $expected)
	{
		$this->assertEquals($expected, Jam_Query_Builder::resolve_attribute_name($name, $context_model));
	}

	public function data_resolve_join()
	{
		return array(
			array(array('test_author', NULL, 'test_post'), 'JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`)'),
			array(array('test_posts', NULL, 'test_author'), 'JOIN `test_posts` ON (`test_posts`.`test_author_id` = `test_authors`.`id`)'),
			array(array('test_categories', NULL, 'test_post'), 'JOIN `test_categories_test_posts` ON (`test_categories_test_posts`.`test_post_id` = `test_posts`.`id`) JOIN `test_categories` ON (`test_categories`.`id` = `test_categories_test_posts`.`test_category_id`)'),
			array(array('test_posts', NULL, 'test_blog'), 'JOIN `test_posts` ON (`test_posts`.`test_blog_id` = `test_blogs`.`id`)'),
		);
	}

	/**
	 * @dataProvider data_resolve_join
	 */
	public function test_resolve_join($arguments, $expected)
	{
		$join = call_user_func_array('Jam_Query_Builder::resolve_join', $arguments);

		$this->assertEquals($expected, (string) $join);
	}
}
