<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests Hasmany fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.hasmany
 */
class Jam_Association_HasmanyTest extends Testcase_Database {

	public $meta;

	public function setUp()
	{
		parent::setUp();

		$this->meta = $this->getMock('Jam_Meta', array('field'), array('test_author'));
	}

	public function data_initialize()
	{
		return array(
			array('test_posts', array(), 'test_author_id', NULL, NULL),
			array('posts', array('foreign_model' => 'test_post'), 'test_author_id', NULL, NULL),
			array('test_posts', array('as' => 'poster'), 'poster_id', 'poster_model', NULL),
		);
	}

	/**
	 * @dataProvider data_initialize
	 */
	public function test_initialize($name, $options, $expected_foreign_key, $expected_polymorphic_key, $expected_exception)
	{
		if ($expected_exception)
		{
			$this->setExpectedException('Kohana_Exception', $expected_exception);
		}

		$association = new Jam_Association_Hasmany($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($name, $association->name);
		$this->assertEquals($expected_foreign_key, $association->foreign_key);
		$this->assertEquals($expected_polymorphic_key, $association->polymorphic_key);

		$this->assertEquals((bool) $expected_polymorphic_key, $association->is_polymorphic());
	}


	public function data_join()
	{
		return array(
			array('test_posts', array(), NULL, NULL, 'JOIN `test_posts` ON (`test_posts`.`test_author_id` = `test_authors`.`id`)'),
			array('test_posts', array(), 'Posts', 'LEFT', 'LEFT JOIN `test_posts` AS `Posts` ON (`Posts`.`test_author_id` = `test_authors`.`id`)'),
			array('posts', array('foreign_model' => 'test_post'), NULL, NULL, 'JOIN `test_posts` ON (`test_posts`.`test_author_id` = `test_authors`.`id`)'),
			array('test_posts', array('as' => 'poster'), NULL, NULL, 'JOIN `test_posts` ON (`test_posts`.`poster_id` = `test_authors`.`id` AND `test_posts`.`poster_model` = \'test_author\')'),
			array('test_posts', array('as' => 'poster'), 'articles', NULL, 'JOIN `test_posts` AS `articles` ON (`articles`.`poster_id` = `test_authors`.`id` AND `articles`.`poster_model` = \'test_author\')'),
		);
	}

	/**
	 * @dataProvider data_join
	 */
	public function test_join($name, $options, $table, $type, $expected_sql)
	{
		$association = new Jam_Association_Hasmany($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($expected_sql, (string) $association->join($table, $type));
	}

	public function test_set()
	{
		$association = new Jam_Association_Hasmany(array('inverse_of' => 'test_author'));
		$association->initialize($this->meta, 'test_posts');

		$posts = array(Jam::build('test_post'), Jam::build('test_post'));
		$author = Jam::build('test_author');

		$association->set($author, $posts, TRUE);

		$this->assertSame($author, $posts[0]->test_author);
		$this->assertSame($author, $posts[1]->test_author);

		$association = new Jam_Association_Hasmany(array('as' => 'test_author'));
		$association->initialize($this->meta, 'test_posts');

		$posts = array(Jam::build('test_post'), Jam::build('test_post'));
		$author = Jam::build('test_author');

		$association->set($author, $posts, TRUE);

		$this->assertSame($author, $posts[0]->test_author);
		$this->assertSame($author, $posts[1]->test_author);

	}

	public function test_load_fields()
	{
		$association = new Jam_Association_Hasmany(array('inverse_of' => 'test_author'));
		$association->initialize($this->meta, 'test_posts');

		$posts = array(array('id' => 1, 'name' => 'Hasmany Test 1'), array('id' => 2, 'name' => 'Hasmany Test 2'));
		$author = Jam::build('test_author');

		$value = $association->load_fields($author, $posts);

		foreach ($posts as $i => $post)
		{
			$this->assertTrue($value[$i]->loaded());
			$this->assertEquals($posts[$i]['id'], $value[$i]->id());
			$this->assertEquals($posts[$i]['name'], $value[$i]->name());
			$this->assertSame($author, $value[$i]->test_author);
		}
	}

	public function data_get()
	{
		return array(
			array('test_posts', array(), NULL, NULL, 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`test_author_id` = 1'),
			array('posts', array('foreign_model' => 'test_post'), NULL, NULL, 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`test_author_id` = 1'),
			array('test_posts', array('foreign_key' => 'author_id'), NULL, NULL, 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`author_id` = 1'),
			array('test_posts', array('as' => 'poster'), NULL, NULL, 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`poster_id` = 1 AND `test_posts`.`poster_model` = \'test_author\''),

			array('test_posts', array(), array(1,2), array(1, 2), 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`test_author_id` = 1'),
			array('test_posts', array(), array(array('id' => 5),2), array(5, 2), 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`test_author_id` = 1'),
		);
	}

	/**
	 * @dataProvider data_get
	 */
	public function test_get($name, $options, $value, $expected_ids, $expected_sql)
	{
		$association = new Jam_Association_Hasmany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_author')->load_fields(array('id' => 1));

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
			array('test_posts', array(), 'DELETE FROM `test_posts` WHERE `test_posts`.`test_author_id` = 1'),
			array('posts', array('foreign_model' => 'test_post'), 'DELETE FROM `test_posts` WHERE `test_posts`.`test_author_id` = 1'),
			array('test_posts', array('foreign_key' => 'author_id'), 'DELETE FROM `test_posts` WHERE `test_posts`.`author_id` = 1'),
			array('test_posts', array('as' => 'poster'), 'DELETE FROM `test_posts` WHERE `test_posts`.`poster_id` = 1 AND `test_posts`.`poster_model` = \'test_author\''),
		);
	}

	/**
	 * @dataProvider data_erase_query
	 */
	public function test_erase_query($name, $options, $expected_sql)
	{
		$association = new Jam_Association_Hasmany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_author')->load_fields(array('id' => 1));
		$this->assertEquals($expected_sql, (string) $association->erase_query($model));
	}

	public function data_nullify_query()
	{
		return array(
			array('test_posts', array(), 'UPDATE `test_posts` SET `test_author_id` = NULL WHERE `test_posts`.`test_author_id` = 1'),
			array('posts', array('foreign_model' => 'test_post'), 'UPDATE `test_posts` SET `test_author_id` = NULL WHERE `test_posts`.`test_author_id` = 1'),
			array('test_posts', array('foreign_key' => 'author_id'), 'UPDATE `test_posts` SET `author_id` = NULL WHERE `test_posts`.`author_id` = 1'),
			array('test_posts', array('as' => 'poster'), 'UPDATE `test_posts` SET `poster_id` = NULL, `poster_model` = NULL WHERE `test_posts`.`poster_id` = 1 AND `test_posts`.`poster_model` = \'test_author\''),
		);
	}

	/**
	 * @dataProvider data_nullify_query
	 */
	public function test_nullify_query($name, $options, $expected_sql)
	{
		$association = new Jam_Association_Hasmany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_author')->load_fields(array('id' => 1));

		$this->assertEquals($expected_sql, (string) $association->nullify_query($model));
	}

	public function data_remove_items_query()
	{
		return array(
			array('test_posts', array(), array(1,2,3), 'UPDATE `test_posts` SET `test_author_id` = NULL WHERE `test_posts`.`id` IN (1, 2, 3)'),
			array('posts', array('foreign_model' => 'test_post'), array(1,2,3), 'UPDATE `test_posts` SET `test_author_id` = NULL WHERE `test_posts`.`id` IN (1, 2, 3)'),
			array('test_posts', array('foreign_key' => 'author_id'), array(1,2,3), 'UPDATE `test_posts` SET `author_id` = NULL WHERE `test_posts`.`id` IN (1, 2, 3)'),
			array('test_posts', array('as' => 'poster'), array(1,2,3), 'UPDATE `test_posts` SET `poster_id` = NULL, `poster_model` = NULL WHERE `test_posts`.`id` IN (1, 2, 3)'),
		);
	}

	/**
	 * @dataProvider data_remove_items_query
	 */
	public function test_remove_items_query($name, $options, $ids, $expected_sql)
	{
		$association = new Jam_Association_Hasmany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_author')->load_fields(array('id' => 1));

		$this->assertEquals($expected_sql, (string) $association->remove_items_query($model, $ids));
	}

	public function data_add_items_query()
	{
		return array(
			array('test_posts', array(), array(1,2,3), 'UPDATE `test_posts` SET `test_author_id` = 1 WHERE `test_posts`.`id` IN (1, 2, 3)'),
			array('posts', array('foreign_model' => 'test_post'), array(1,2,3), 'UPDATE `test_posts` SET `test_author_id` = 1 WHERE `test_posts`.`id` IN (1, 2, 3)'),
			array('test_posts', array('foreign_key' => 'author_id'), array(1,2,3), 'UPDATE `test_posts` SET `author_id` = 1 WHERE `test_posts`.`id` IN (1, 2, 3)'),
			array('test_posts', array('as' => 'poster'), array(1,2,3), 'UPDATE `test_posts` SET `poster_id` = 1, `poster_model` = \'test_author\' WHERE `test_posts`.`id` IN (1, 2, 3)'),
		);
	}

	/**
	 * @dataProvider data_add_items_query
	 */
	public function test_add_items_query($name, $options, $ids, $expected_sql)
	{
		$association = new Jam_Association_Hasmany($options);
		$association->initialize($this->meta, $name);

		$model = Jam::build('test_author')->load_fields(array('id' => 1));

		$this->assertEquals($expected_sql, (string) $association->add_items_query($model, $ids));
	}

	public function test_save()
	{
		$model = Jam::build('test_author')->load_fields(array('id' => 1));

		$association = $this->getMock('Jam_Association_Hasmany', array('add_items_query', 'remove_items_query'), array(array()));
		$association->initialize($this->meta, 'test_posts');

		$collection = $this->getMock('Jam_Array_Association', array('original_ids', 'ids'));

		$dummy = $this->getMock('Jam_Query_Builder_Update', array('execute'), array('test_post'));
		$dummy
			->expects($this->exactly(2))
			->method('execute');

		$collection
			->expects($this->exactly(2))
			->method('original_ids')
			->will($this->returnValue(array(1, 2, 3)));

		$collection
			->expects($this->exactly(2))
			->method('ids')
			->will($this->returnValue(array(3, 4, 5)));

		$association
			->expects($this->once())
			->method('add_items_query')
			->with($this->equalTo($model), $this->equalTo(array(4, 5)))
			->will($this->returnValue($dummy));

		$association
			->expects($this->once())
			->method('remove_items_query')
			->with($this->equalTo($model), $this->equalTo(array(1, 2)))
			->will($this->returnValue($dummy));

		$association->save($model, $collection);
	}
}
