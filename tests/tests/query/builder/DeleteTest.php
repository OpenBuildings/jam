<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.delete
 */
class Jam_Query_Builder_DeleteTest extends PHPUnit_Framework_TestCase {

	public function test_constructor()
	{
		$select = Jam_Query_Builder_Delete::factory('test_author');
		$this->assertInstanceOf('Jam_Query_Builder_Delete', $select);

		$this->assertEquals(Jam::meta('test_author'), $select->meta());

		$this->assertEquals('DELETE FROM `test_authors`', (string) $select);
	}


	public function test_where()
	{
		$select = new Jam_Query_Builder_Delete('test_post');

		$select
			->where('test_post.test_author_id', '=', 10);

		$this->assertEquals('DELETE FROM `test_posts` WHERE `test_posts`.`test_author_id` = 10', (string) $select);
	}

	public function test_order_by()
	{
		$select = new Jam_Query_Builder_Delete('test_post');

		$select
			->order_by('test_post.test_author_id', 'DESC');

		$this->assertEquals('DELETE FROM `test_posts` ORDER BY `test_posts`.`test_author_id` DESC', (string) $select);
	}
}

