<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.select
 */
class Jam_Query_Builder_SelectTest extends Unittest_Jam_TestCase {

	public function test_constructor()
	{
		$select = Jam::select('test_author');
		$this->assertInstanceOf('Jam_Query_Builder_Select', $select);

		$this->assertEquals(Jam::meta('test_author'), $select->meta());

		$this->assertEquals('SELECT `test_authors`.* FROM `test_authors`', (string) $select);
	}

	public function test_from()
	{
		$select = Jam::select('test_author');
		$select
			->from(array('test_author', 'author_table'))
			->select('author_table.*');

		$this->assertEquals('SELECT `author_table`.* FROM `test_authors` AS `author_table`', (string) $select);
	}

	public function test_alias()
	{
		$select = Jam::select('test_author');
		$select
			->from(array('test_author', 'author_table'))
			->select('author_table.*')
			->where('author_table.name', '=', 'Alex');

		$this->assertEquals('SELECT `author_table`.* FROM `test_authors` AS `author_table` WHERE `author_table`.`name` = \'Alex\'', (string) $select);
	}

	public function test_join()
	{
		$select = Jam::select('test_post');

		$select
			->join('test_author', NULL, Jam::NEST_JOIN)
				->join('test_categories')
			->end();

		$this->assertEquals('SELECT `test_posts`.* FROM `test_posts` JOIN `test_authors` ON `test_authors`.`id` = `test_posts`.`test_author_id` JOIN `test_categories` ON `test_categories`.`test_author_id` = `test_authors`.`id`', (string) $select);	
	}
}