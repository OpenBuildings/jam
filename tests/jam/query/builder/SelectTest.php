<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.select
 */
class Jam_Query_Builder_SelectTest extends Unittest_TestCase {

	public function test_constructor()
	{
		$select = new Jam_Query_Builder_Select('test_author');
		$this->assertInstanceOf('Jam_Query_Builder_Select', $select);

		$this->assertEquals('test_author', $select->model());

		$this->assertEquals('SELECT `test_authors`.* FROM `test_authors`', (string) $select);
	}

	public function test_from()
	{
		$select = new Jam_Query_Builder_Select('test_author');
		$select
			->from(array('test_author', 'author_table'))
			->select('author_table.*');

		$this->assertEquals('SELECT `author_table`.* FROM `test_authors` AS `author_table`', (string) $select);
	}

	public function test_alias()
	{
		$select = new Jam_Query_Builder_Select('test_author');
		$select
			->from(array('test_author', 'author_table'))
			->select('author_table.*')
			->where('author_table.name', '=', 'Alex');

		$this->assertEquals('SELECT `author_table`.* FROM `test_authors` AS `author_table` WHERE `author_table`.`name` = \'Alex\'', (string) $select);
	}

	public function test_join()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->join('test_author');

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`)', (string) $select);	
	}

	public function test_having()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->having('test_post.test_author_id', '=', 10);

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` HAVING `test_posts`.`test_author_id` = 10', (string) $select);	
	}

	public function test_where()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->where('test_post.test_author_id', '=', 10);

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`test_author_id` = 10', (string) $select);	
	}

	public function test_order_by()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->order_by('test_post.test_author_id', 'DESC');

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` ORDER BY `test_posts`.`test_author_id` DESC', (string) $select);	
	}

	public function test_select_count()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select->select_count();

		$this->assertEquals('SELECT COUNT(*) AS `total` FROM `test_posts`', (string) $select);	

		$select = new Jam_Query_Builder_Select('test_post');

		$select->select_count(':name_key');

		$this->assertEquals('SELECT COUNT(`test_posts`.`name`) AS `total` FROM `test_posts`', (string) $select);	
	}

	public function test_nested_join()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->join_nested('test_author')
				->join('test_categories')
			->end();

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`) JOIN `test_categories` ON (`test_categories`.`test_author_id` = `test_authors`.`id`)', (string) $select);	
	}

	public function test_manytomany_join()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->join('test_categories');

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` JOIN `test_categories` ON (`test_categories`.`id` = `test_categories_test_posts`.`test_category_id`) JOIN `test_categories_test_posts` ON (`test_categories_test_posts`.`test_post_id` = `test_posts`.`id`)', (string) $select);	
	}

}