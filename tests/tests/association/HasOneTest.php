<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests Belongsto associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.hasone
 */
class Jam_Association_HasoneTest extends Testcase_Database {

	public function setUp()
	{
		parent::setUp();

		$this->meta = $this->getMock('Jam_Meta', array('field'), array('test_author'));
	}

	public function data_initialize()
	{
		return array(
			array('test_post', array(), 'test_post', 'test_author_id', NULL),
			array('cover_image', array('foreign_model' => 'post'), 'post', 'test_author_id', NULL),
			array('test_post', array('foreign_key' => 'cover_uid'), 'test_post', 'cover_uid', NULL),
			array('test_post', array('as' => 'cover'), 'test_post', 'cover_id', 'cover_model'),
			array('creator', array('foreign_model' => 'test_cover', 'as' => 'cover'), 'test_cover', 'cover_id', 'cover_model'),
		);
	}

	/**
	 * @dataProvider data_initialize
	 */
	public function test_initialize($name, $options, $expected_foreign_model, $expected_foreign_key, $expected_polymorphic_key)
	{
		$association = new Jam_Association_Hasone($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($name, $association->name);
		$this->assertEquals($expected_foreign_key, $association->foreign_key);
		$this->assertEquals($expected_polymorphic_key, $association->polymorphic_key);
		$this->assertEquals($expected_foreign_model, $association->foreign_model);

		$this->assertEquals((bool) $expected_polymorphic_key, $association->is_polymorphic());
	}

	public function data_join()
	{
		return array(
			array('test_post', array(), NULL, NULL, 'JOIN `test_posts` ON (`test_posts`.`test_author_id` = `test_authors`.`id`)'),
			array('test_post', array(), 'Posts', 'LEFT', 'LEFT JOIN `test_posts` AS `Posts` ON (`Posts`.`test_author_id` = `test_authors`.`id`)'),
			array('post', array('foreign_model' => 'test_post'), NULL, NULL, 'JOIN `test_posts` ON (`test_posts`.`test_author_id` = `test_authors`.`id`)'),
			array('test_post', array('as' => 'poster'), NULL, NULL, 'JOIN `test_posts` ON (`test_posts`.`poster_id` = `test_authors`.`id` AND `test_posts`.`poster_model` = \'test_author\')'),
			array('test_post', array('as' => 'poster'), 'articles', NULL, 'JOIN `test_posts` AS `articles` ON (`articles`.`poster_id` = `test_authors`.`id` AND `articles`.`poster_model` = \'test_author\')'),
		);
	}

	/**
	 * @dataProvider data_join
	 */
	public function test_join($name, $options, $table, $type, $expected_sql)
	{
		$association = new Jam_Association_Hasone($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($expected_sql, (string) $association->join($table, $type));
	}

	public function test_load_fields()
	{
		$association = new Jam_Association_Hasone(array());
		$association->initialize($this->meta, 'test_post');

		$model = new Model_Test_Author();
		$value = $association->load_fields($model, array('id' => 2, 'name' => 'Test'));

		$this->assertInstanceOf('Model_Test_Post', $value);
		$this->assertTrue($value->loaded());
		$this->assertEquals(2, $value->id());
		$this->assertEquals('Test', $value->name());
	}

	public function test_get()
	{
		$association = $this->getMock('Jam_Association_Hasone', array('_find_item'), array());
		$association->initialize($this->meta, 'test_post');

		$author = Jam::build('test_author')->load_fields(array('id' => 1));
		$post = Jam::build('test_post')->load_fields(array('id' => 1, 'test_author_id' => 10, 'test_author_model' => 'test_category'));

		// Check for Null Value changed
		$this->assertNull($association->get($author, NULL, TRUE));

		// Check for Null Value not changed
		$association
			->expects($this->at(0))
			->method('_find_item')
			->with($this->equalTo('test_post'), $this->equalTo($author))
			->will($this->returnValue($post));

		$this->assertSame($post, $association->get($author, NULL, FALSE));

		// Check for Null Value not changed
		$association
			->expects($this->at(0))
			->method('_find_item')
			->with($this->equalTo('test_post'), $this->equalTo(10))
			->will($this->returnValue($post));

		$this->assertSame($post, $association->get($author, 10, TRUE));

		// Check for Null Value not changed
		$association
			->expects($this->at(0))
			->method('_find_item')
			->with($this->equalTo('test_post'), $this->equalTo(12))
			->will($this->returnValue($post));

		$returned_post = $association->get($author, array('id' => 12, 'title' => 'new post title'), TRUE);
		$this->assertSame($post, $returned_post);
		$this->assertEquals('new post title', $returned_post->title);
	}

	public function test_check()
	{
		$association = new Jam_Association_Hasone(array());
		$association->initialize($this->meta, 'test_post');

		$author = Jam::build('test_author')->load_fields(array('id' => 1));
		$post = $this->getMock('Model_Test_Post', array('check'), array('test_post'));
		$post->load_fields(array('id' => 1, 'test_author_id' => 10, 'test_author_model' => 'test_category'));
		$post
			->expects($this->once())
			->method('check')
			->will($this->returnValue(TRUE));

		$author->test_post = $post;

		$association->model_after_check($author, new Jam_Event_Data(array()), array('test_post' => $post));
	}

	public function test_build()
	{
		$association = new Jam_Association_Hasone(array('inverse_of' => 'test_author'));
		$association->initialize($this->meta, 'test_post');

		$model = new Model_Test_Author();
		$value = $association->build($model);
		$this->assertInstanceOf('Model_Test_Post', $value);
		$this->assertSame($value->test_author, $model);

		$association = new Jam_Association_Hasone(array('as' => 'test_author'));
		$association->initialize($this->meta, 'test_post');

		$model = new Model_Test_Author();
		$value = $association->build($model);
		$this->assertInstanceOf('Model_Test_Post', $value);
		$this->assertSame($value->test_author, $model);
	}

	public function data_query_builder()
	{
		return array(
			array(array(), 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`test_author_id` = 5'),
			array(array('as' => 'own_post'), 'SELECT `test_posts`.* FROM `test_posts` WHERE `test_posts`.`own_post_id` = 5 AND `test_posts`.`own_post_model` = \'test_author\''),
		);
	}

	/**
	 * @dataProvider data_query_builder
	 */
	public function test_query_builder($options, $expected_sql)
	{
		$association = new Jam_Association_Hasone($options);
		$association->initialize($this->meta, 'test_post');

		$author = Jam::build('test_author')->load_fields(array('id' => 5));

		$this->assertEquals($expected_sql, (string) $association->query_builder('select', $author));
	}

	public function data_update_builder()
	{
		return array(
			array(array(), 10, NULL, 'UPDATE `test_posts` SET `test_author_id` = 10 WHERE `test_posts`.`test_author_id` = 5'),
			array(array('as' => 'own_post'), 12, 'test_author2', 'UPDATE `test_posts` SET `own_post_id` = 12, `own_post_model` = \'test_author2\' WHERE `test_posts`.`own_post_id` = 5 AND `test_posts`.`own_post_model` = \'test_author\''),
		);
	}

	/**
	 * @dataProvider data_update_builder
	 */
	public function test_update_builder($options, $id, $model, $expected_sql)
	{
		$association = new Jam_Association_Hasone($options);
		$association->initialize($this->meta, 'test_post');

		$author = Jam::build('test_author')->load_fields(array('id' => 5));

		$this->assertEquals($expected_sql, (string) $association->update_query($author, $id, $model));
	}

	public function test_set_null()
	{
		$author = Jam::find_insist('test_author', 1);
		$author->test_post = NULL;
		$this->assertNull($author->test_post);
	}

	public function test_set_null_and_save()
	{
		$author = Jam::find_insist('test_author', 1);
		$author->test_post = NULL;
		$author->save();
		$this->assertNull($author->test_post);
	}

}
