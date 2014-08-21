<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests Manytomany fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.manytomany
 */
class Jam_Association_ManytomanyTest extends Testcase_Database {

	public $meta;

	public function setUp()
	{
		parent::setUp();

		$this->meta = new Jam_Meta('test_blog');
	}

	public function data_initialize()
	{
		return array(
			array('test_tags', array(), 'test_blogs_test_tags', 'test_blog_id', 'test_tag_id'),
			array('tags', array('foreign_model' => 'test_post'), 'test_blogs_test_posts', 'test_blog_id', 'test_post_id'),
			array('test_tags', array('join_table' => 'poster'), 'poster', 'test_blog_id', 'test_tag_id'),
			array('test_tags', array('foreign_key' => 'blog_id', 'foreign_model' => 'test_author'), 'test_authors_test_blogs', 'blog_id', 'test_author_id'),
			array('test_tags', array('association_foreign_key' => 'blog_id', 'join_table' => 'poster'), 'poster', 'test_blog_id', 'blog_id'),
		);
	}

	/**
	 * @dataProvider data_initialize
	 */
	public function test_initialize($name, $options, $expected_join_table, $expected_foreign_key, $expected_association_foreign_key)
	{
		$association = new Jam_Association_Manytomany($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($name, $association->name);
		$this->assertEquals($expected_join_table, $association->join_table);
		$this->assertEquals($expected_foreign_key, $association->foreign_key);
		$this->assertEquals($expected_association_foreign_key, $association->association_foreign_key);

		$this->assertEquals(FALSE, $association->is_polymorphic());
	}


	public function data_join()
	{
		return array(
			array('test_tags', array(), NULL, NULL, 'JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_blog_id` = `test_blogs`.`id`) JOIN `test_tags` ON (`test_tags`.`id` = `test_blogs_test_tags`.`test_tag_id`)'),
			array('tags', array('foreign_model' => 'test_tag'), NULL, NULL, 'JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_blog_id` = `test_blogs`.`id`) JOIN `test_tags` ON (`test_tags`.`id` = `test_blogs_test_tags`.`test_tag_id`)'),
			array('test_tags', array('join_table' => 'permissions'), NULL, NULL, 'JOIN `permissions` ON (`permissions`.`test_blog_id` = `test_blogs`.`id`) JOIN `test_tags` ON (`test_tags`.`id` = `permissions`.`test_tag_id`)'),
			array('test_tags', array('join_table' => 'permissions', 'join_table_paranoid' => TRUE), NULL, NULL, 'JOIN `permissions` ON (`permissions`.`test_blog_id` = `test_blogs`.`id` AND `permissions`.`is_deleted` = 0) JOIN `test_tags` ON (`test_tags`.`id` = `permissions`.`test_tag_id`)'),
			array('test_tags', array('join_table' => 'permissions', 'join_table_paranoid' => 'deleted'), NULL, NULL, 'JOIN `permissions` ON (`permissions`.`test_blog_id` = `test_blogs`.`id` AND `permissions`.`deleted` = 0) JOIN `test_tags` ON (`test_tags`.`id` = `permissions`.`test_tag_id`)'),
			array('test_tags', array('foreign_key' => 'test_id'), NULL, NULL, 'JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_id` = `test_blogs`.`id`) JOIN `test_tags` ON (`test_tags`.`id` = `test_blogs_test_tags`.`test_tag_id`)'),
			array('test_tags', array('association_foreign_key' => 'test_id'), NULL, NULL, 'JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_blog_id` = `test_blogs`.`id`) JOIN `test_tags` ON (`test_tags`.`id` = `test_blogs_test_tags`.`test_id`)'),
			array('test_tags', array(), 'Posts', 'LEFT', 'LEFT JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_blog_id` = `test_blogs`.`id`) LEFT JOIN `test_tags` AS `Posts` ON (`Posts`.`id` = `test_blogs_test_tags`.`test_tag_id`)'),
		);
	}

	/**
	 * @dataProvider data_join
	 */
	public function test_join($name, $options, $alias, $type, $expected_sql)
	{
		$association = new Jam_Association_Manytomany($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($expected_sql, (string) $association->join($alias, $type));
	}

	public function data_get()
	{
		return array(
			array('test_tags', array(), NULL, NULL, 'SELECT `test_tags`.* FROM `test_tags` JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_tag_id` = `test_tags`.`id`) WHERE `test_blogs_test_tags`.`test_blog_id` = 1'),
			array('tags', array('foreign_model' => 'test_tag'), NULL, NULL, 'SELECT `test_tags`.* FROM `test_tags` JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_tag_id` = `test_tags`.`id`) WHERE `test_blogs_test_tags`.`test_blog_id` = 1'),

			array('test_tags', array('join_table' => 'permissions'), NULL, NULL, 'SELECT `test_tags`.* FROM `test_tags` JOIN `permissions` ON (`permissions`.`test_tag_id` = `test_tags`.`id`) WHERE `permissions`.`test_blog_id` = 1'),
			array('test_tags', array('join_table' => 'permissions', 'join_table_paranoid' => TRUE), NULL, NULL, 'SELECT `test_tags`.* FROM `test_tags` JOIN `permissions` ON (`permissions`.`test_tag_id` = `test_tags`.`id`) WHERE `permissions`.`test_blog_id` = 1 AND `permissions`.`is_deleted` = \'0\''),
			array('test_tags', array('join_table' => 'permissions', 'join_table_paranoid' => 'deleted'), NULL, NULL, 'SELECT `test_tags`.* FROM `test_tags` JOIN `permissions` ON (`permissions`.`test_tag_id` = `test_tags`.`id`) WHERE `permissions`.`test_blog_id` = 1 AND `permissions`.`deleted` = \'0\''),
			array('test_tags', array('foreign_key' => 'test_id'), NULL, NULL, 'SELECT `test_tags`.* FROM `test_tags` JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_tag_id` = `test_tags`.`id`) WHERE `test_blogs_test_tags`.`test_id` = 1'),
			array('test_tags', array('association_foreign_key' => 'test_id'), NULL, NULL, 'SELECT `test_tags`.* FROM `test_tags` JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_id` = `test_tags`.`id`) WHERE `test_blogs_test_tags`.`test_blog_id` = 1'),

			array('test_tags', array(), array(1, 2), array(1, 2), 'SELECT `test_tags`.* FROM `test_tags` JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_tag_id` = `test_tags`.`id`) WHERE `test_blogs_test_tags`.`test_blog_id` = 1'),
			array('test_tags', array(), array(array('id' => 5), 2), array(5, 2), 'SELECT `test_tags`.* FROM `test_tags` JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_tag_id` = `test_tags`.`id`) WHERE `test_blogs_test_tags`.`test_blog_id` = 1'),
		);
	}

	/**
	 * @dataProvider data_get
	 */
	public function test_get($name, $options, $value, $expected_ids, $expected_sql)
	{
		$association = new Jam_Association_Manytomany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_blog')->load_fields(array('id' => 1));

		$result = $association->get($model, $value, (bool) $value);

		$this->assertInstanceOf('Jam_Array_Association', $result);

		$this->assertEquals($expected_sql, (string) $result);

		if ($expected_ids !== NULL)
		{
			$this->assertEquals($expected_ids, $result->ids());
		}
	}

	public function data_erase_query()
	{
		return array(
			array('test_tags', array(), 'DELETE FROM `test_blogs_test_tags` WHERE `test_blog_id` = 1'),
			array('tags', array('foreign_model' => 'test_tag'), 'DELETE FROM `test_blogs_test_tags` WHERE `test_blog_id` = 1'),
			array('test_tags', array('foreign_key' => 'test_id'), 'DELETE FROM `test_blogs_test_tags` WHERE `test_id` = 1'),
			array('test_tags', array('join_table' => 'permissions'), 'DELETE FROM `permissions` WHERE `test_blog_id` = 1'),
		);
	}

	/**
	 * @dataProvider data_erase_query
	 */
	public function test_erase_query($name, $options, $expected_sql)
	{
		$association = new Jam_Association_Manytomany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_blog')->load_fields(array('id' => 1));

		$this->assertEquals($expected_sql, (string) $association->erase_query($model));
	}

	public function data_remove_items_query()
	{
		return array(
			array('test_tags', array(), array(1,2,3), 'DELETE FROM `test_blogs_test_tags` WHERE `test_blog_id` = 1 AND `test_tag_id` IN (1, 2, 3)'),
			array('tags', array('foreign_model' => 'test_tag'), array(1,2,3), 'DELETE FROM `test_blogs_test_tags` WHERE `test_blog_id` = 1 AND `test_tag_id` IN (1, 2, 3)'),
			array('test_tags', array('association_foreign_key' => 'test_id'), array(1,2,3), 'DELETE FROM `test_blogs_test_tags` WHERE `test_blog_id` = 1 AND `test_id` IN (1, 2, 3)'),
			array('test_tags', array('join_table' => 'permissions'), array(1,2,3), 'DELETE FROM `permissions` WHERE `test_blog_id` = 1 AND `test_tag_id` IN (1, 2, 3)'),
		);
	}

	/**
	 * @dataProvider data_remove_items_query
	 */
	public function test_remove_items_query($name, $options, $ids, $expected_sql)
	{
		$association = new Jam_Association_Manytomany($options);
		$association->initialize($this->meta, $name);
		$model = Jam::build('test_blog')->load_fields(array('id' => 1));

		$this->assertEquals($expected_sql, (string) $association->remove_items_query($model, $ids));
	}

	public function data_add_items_query()
	{
		return array(
			array('test_tags', array(), array(1,2,3), 'INSERT INTO `test_blogs_test_tags` (`test_blog_id`, `test_tag_id`) VALUES (1, 1), (1, 2), (1, 3)'),
			array('tags', array('foreign_model' => 'test_tag'), array(1,2,3), 'INSERT INTO `test_blogs_test_tags` (`test_blog_id`, `test_tag_id`) VALUES (1, 1), (1, 2), (1, 3)'),
			array('test_tags', array('foreign_key' => 'test_id'), array(1,2,3), 'INSERT INTO `test_blogs_test_tags` (`test_id`, `test_tag_id`) VALUES (1, 1), (1, 2), (1, 3)'),
			array('test_tags', array('association_foreign_key' => 'test_id'), array(1,2,3), 'INSERT INTO `test_blogs_test_tags` (`test_blog_id`, `test_id`) VALUES (1, 1), (1, 2), (1, 3)'),
			array('test_tags', array('join_table' => 'permissions'), array(1,2,3), 'INSERT INTO `permissions` (`test_blog_id`, `test_tag_id`) VALUES (1, 1), (1, 2), (1, 3)'),
		);
	}

	/**
	 * @dataProvider data_add_items_query
	 */
	public function test_add_items_query($name, $options, $ids, $expected_sql)
	{
		$association = new Jam_Association_Manytomany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_blog')->load_fields(array('id' => 1));

		$this->assertEquals($expected_sql, (string) $association->add_items_query($model, $ids));
	}

}

