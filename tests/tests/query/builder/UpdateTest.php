<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.update
 */
class Jam_Query_Builder_UpdateTest extends PHPUnit_Framework_TestCase {

	public function test_constructor()
	{
		$select = Jam_Query_Builder_Update::factory('test_author');

		$select
			->value('name', 'Test');

		$this->assertInstanceOf('Jam_Query_Builder_Update', $select);

		$this->assertEquals(Jam::meta('test_author'), $select->meta());

		$this->assertEquals('UPDATE `test_authors` SET `name` = \'Test\'', (string) $select);
	}


	public function test_where()
	{
		$select = new Jam_Query_Builder_Update('test_post');

		$select
			->value('name', 'Test')
			->where('test_post.test_author_id', '=', 10);

		$this->assertEquals('UPDATE `test_posts` SET `name` = \'Test\' WHERE `test_posts`.`test_author_id` = 10', (string) $select);
	}

	public function test_order_by()
	{
		$select = new Jam_Query_Builder_Update('test_post');

		$select
			->value('name', 'Test')
			->order_by('test_post.test_author_id', 'DESC');

		$this->assertEquals('UPDATE `test_posts` SET `name` = \'Test\' ORDER BY `test_posts`.`test_author_id` DESC', (string) $select);
	}
}

