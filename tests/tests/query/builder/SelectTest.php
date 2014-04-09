<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.select
 */
class Jam_Query_Builder_SelectTest extends PHPUnit_Framework_TestCase {

	public function test_constructor()
	{
		$select = Jam_Query_Builder_Select::factory('test_author');
		$this->assertInstanceOf('Jam_Query_Builder_Select', $select);

		$this->assertEquals(Jam::meta('test_author'), $select->meta());

		$this->assertEquals('SELECT `test_authors`.* FROM `test_authors`', (string) $select);
	}

	public function test_from()
	{
		$select = new Jam_Query_Builder_Select('test_author');
		$select
			->from(array('test_author', 'author_table'))
			->select('author_table.*');

		$this->assertEquals('SELECT `author_table`.* FROM `test_authors` AS `author_table`', (string) $select);

		$this->assertEquals('SELECT `author_table`.* FROM `test_authors` AS `author_table`', (string) $select, 'Should not double quote selects');
	}

	public function test_select()
	{
		$select = new Jam_Query_Builder_Select('test_author');
		$select
			->select(array(':primary_key', 'primary_key'))
			->select(array(':name_key', 'name_key'));

		$this->assertEquals('SELECT `test_authors`.`id` AS `primary_key`, `test_authors`.`name` AS `name_key` FROM `test_authors`', (string) $select);
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

	public function test_join_duplicate()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->join('test_author')
			->join('test_author')
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

	public function test_group()
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->group_by('test_post.id');

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` GROUP BY `test_posts`.`id`', (string) $select);
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

	public function data_aggregate_query()
	{
		return array(
			array('MAX', 'name', 'SELECT MAX(`test_posts`.`name`) AS `result` FROM `test_posts`'),
			array('MAX', 'test_post.name', 'SELECT MAX(`test_posts`.`name`) AS `result` FROM `test_posts`'),
			array('COUNT', NULL, 'SELECT COUNT(*) AS `result` FROM `test_posts`'),
			array('COUNT', '*', 'SELECT COUNT(*) AS `result` FROM `test_posts`'),
			array('COUNT', ':name_key', 'SELECT COUNT(`test_posts`.`name`) AS `result` FROM `test_posts`'),
			array('GROUP_CONCAT', ':name_key', 'SELECT GROUP_CONCAT(`test_posts`.`name`) AS `result` FROM `test_posts`'),
		);
	}

	/**
	 * @dataProvider data_aggregate_query
	 */
	public function test_aggregate_query($function, $column, $expected_sql)
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$query = $select->aggregate_query($function, $column);

		$this->assertEquals($expected_sql, (string) $query);
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

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` JOIN `test_categories_test_posts` ON (`test_categories_test_posts`.`test_post_id` = `test_posts`.`id`) JOIN `test_categories` ON (`test_categories`.`id` = `test_categories_test_posts`.`test_category_id`)', (string) $select);
	}

	public function data_except()
	{
		return array(
			array(array('join'), 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`name` = \'Adam\' ORDER BY `test_posts`.`id` DESC'),
			array(array('order_by'), 'SELECT `test_posts`.* FROM `test_posts` JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`) WHERE `test_posts`.`name` = \'Adam\''),
			array(array('order_by', 'where'), 'SELECT `test_posts`.* FROM `test_posts` JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`)'),
			array(array('meta'), NULL),
		);
	}

	/**
	 * @dataProvider data_except
	 */
	public function test_except($except, $expected_sql)
	{
		$select = new Jam_Query_Builder_Select('test_post');

		$select
			->join('test_author')
			->where('name', '=', 'Adam')
			->order_by('id', 'DESC');

		if ($expected_sql === NULL)
		{
			$this->setExpectedException('Kohana_Exception');
		}

		call_user_func_array(array($select, 'except'), $except);

		$this->assertEquals($expected_sql, (string) $select);

	}

}
