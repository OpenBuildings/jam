<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.serialize
 */
class Jam_SerializeTest extends PHPUnit_Framework_TestCase {

	public function test_collection()
	{
		$collection = new Jam_Query_Builder_Collection('test_position');
		$data = array(
			array('id' => 1, 'name' => 'name 1', 'model' => 'test_position'),
			array('id' => 2, 'name' => 'name 2', 'model' => 'test_position'),
			array('id' => 3, 'name' => 'name 3', 'model' => 'test_position'),
		);
		$collection->load_fields($data);

		$unserialized = unserialize(serialize($collection));

		$this->assertCount(3, $unserialized);

		foreach ($data as $i => $item)
		{
			$this->assertTrue($unserialized[$i]->loaded());
			$this->assertEquals($item, $unserialized[$i]->as_array());
		}
	}

	public function test_repeat_collection()
	{
		$test_blog = Jam::build('test_blog');

		$test_blog->test_posts->add(Jam::build('test_post'));

		$test_blog = unserialize(serialize($test_blog));

		$test_blog->test_posts->add(Jam::build('test_post'));

		$test_blog = unserialize(serialize($test_blog));

		$this->assertCount(2, $test_blog->test_posts);
	}


	public function test_deep()
	{
		$blog = Jam::find_or_create('test_blog', array('id' => 10, 'name' => 'created blog'));

		$author = Jam::build('test_author')->load_fields(array(
			'id' => 4,
			'name' => 'Joe',
			'test_posts' => array(
				array(
					'id' => 1,
					'name' => 'hardware',
					'test_categories' => array(
						array('id' => 1, 'name' => 'cat1'),
						array('id' => 2, 'name' => 'cat2'),
					),
					'test_blog' => array(
						'id' => 2,
						'name' => 'loaded'
					)
				),
				array(
					'id' => 2,
					'name' => 'software',
					'test_categories' => array(
						array('id' => 1, 'name' => 'cat1'),
						array('id' => 3, 'name' => 'cat3'),
					),
					'test_blog' => 10
				)
			)
		));

		$post = Jam::build('test_post')->load_fields(array(
			'id' => 3,
			'name' => 'administration',
		));

		$post2 = Jam::build('test_post', array('name' => 'unsaved'));

		$author->test_posts->add($post)->add($post2);

		$serialized_data = serialize($author);

		$unserialized = unserialize($serialized_data);

		$this->assertNotSame($author, $unserialized);
		$this->assertTrue($unserialized->loaded());
		$this->assertEquals($author->as_array(), $unserialized->as_array());

		$this->assertCount(4, $unserialized->test_posts);

		$this->assertTrue($unserialized->test_posts[0]->loaded());
		$this->assertEquals($author->test_posts[0]->as_array(), $unserialized->test_posts[0]->as_array());

		$this->assertNotNull($unserialized->test_posts[0]->test_blog);
		$this->assertTrue($unserialized->test_posts[0]->test_blog->loaded());
		$this->assertEquals($author->test_posts[0]->test_blog->as_array(), $unserialized->test_posts[0]->test_blog->as_array());

		$this->assertNotNull($unserialized->test_posts[1]->test_blog);
		$this->assertTrue($unserialized->test_posts[1]->test_blog->loaded());
		$this->assertEquals($author->test_posts[1]->test_blog->as_array(), $unserialized->test_posts[1]->test_blog->as_array());
		$this->assertEquals($blog->as_array(), $unserialized->test_posts[1]->test_blog->as_array());

		$this->assertTrue($unserialized->test_posts[1]->loaded());
		$this->assertEquals($author->test_posts[1]->as_array(), $unserialized->test_posts[1]->as_array());

		$this->assertTrue($unserialized->test_posts[2]->loaded());
		$this->assertEquals($author->test_posts[2]->as_array(), $unserialized->test_posts[2]->as_array());

		$this->assertFalse($unserialized->test_posts[3]->loaded());
		$this->assertEquals($author->test_posts[3]->as_array(), $unserialized->test_posts[3]->as_array());

		$blog->delete();
	}
}
