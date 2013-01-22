<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Collection.
 *
 * @package Jam
 * @group   jam
 * @group   jam.serialize
 */
class Jam_SerializeTest extends Unittest_Jam_TestCase {

	public function test_collection()
	{
		$collection = new Jam_Query_Builder_Collection('test_position');
		$data = array(
			array('id' => 1, 'name' => 'name 1'),
			array('id' => 2, 'name' => 'name 2'),
			array('id' => 3, 'name' => 'name 3'),
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

	public function test_deep()
	{
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
					)
				),
				array(
					'id' => 2,
					'name' => 'software',
					'test_categories' => array(
						array('id' => 1, 'name' => 'cat1'),
						array('id' => 3, 'name' => 'cat3'),
					)
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

		$this->assertTrue($unserialized->test_posts[1]->loaded());
		$this->assertEquals($author->test_posts[1]->as_array(), $unserialized->test_posts[1]->as_array());

		$this->assertTrue($unserialized->test_posts[2]->loaded());
		$this->assertEquals($author->test_posts[2]->as_array(), $unserialized->test_posts[2]->as_array());

		$this->assertFalse($unserialized->test_posts[3]->loaded());
		$this->assertEquals($author->test_posts[3]->as_array(), $unserialized->test_posts[3]->as_array());
	}
}