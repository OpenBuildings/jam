<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.core
 */
class Jam_AssociationTest extends PHPUnit_Framework_TestCase {

	public function data_primary_key()
	{
		$test_position = Jam::build('test_position')->load_fields(array('id' => 10, 'name' => 'name'));

		return array(
			array('test_position', NULL, NULL),
			array('test_position', 1, 1),
			array('test_position', '1', 1),
			array('test_position', '256', 256),
			array('test_position', '256abc', '256abc'),
			array('test_position', 'test', 'test'),
			array('test_position', $test_position, 10),
			array('test_position', array('id' => 10), 10),
			array('test_position', array('name' => 10), NULL),
		);
	}

	/**
	 * @covers Jam_Association::primary_key
	 * @dataProvider data_primary_key
	 */
	public function test_primary_key($model_name, $value, $expected_primary_key)
	{
		$this->assertSame($expected_primary_key, Jam_Association::primary_key($model_name, $value));
	}

	public function test_associated()
	{
		$blog = Jam::build('test_blog')->load_fields(array('id' => 2, 'name' => 'Blog Name'));
		$expected_sql = 'SELECT `test_tags`.* FROM `test_tags` JOIN `test_blogs_test_tags` ON (`test_blogs_test_tags`.`test_tag_id` = `test_tags`.`id`) WHERE `test_blogs_test_tags`.`test_blog_id` = 2';

		$this->assertEquals($expected_sql, (string) $blog->test_tags);

		$blog->test_tags->where('name','=', 'some name');

		$this->assertEquals($expected_sql, (string) $blog->test_tags);
	}

	public function test_inverse_of_with_serialize()
	{
		$test_blog = Jam::build('test_blog');
		$post1 = Jam::build('test_post');
		$test_blog->test_posts->add($post1);
		$test_blog = unserialize(serialize($test_blog));
		foreach ($test_blog->test_posts as $test_post)
		{
			$this->assertSame($test_blog, $test_post->test_blog);
		}
		$this->assertSame($test_blog, $test_blog->test_posts[0]->test_blog);
	}
}
