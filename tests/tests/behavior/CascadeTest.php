<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.cascade
 */
class Jam_Behavior_CascadeTest extends Testcase_Database {

	public function data_get_current_children()
	{
		$children = array('test_blog' => array('test_posts' => array('test_tags', 'test_author', 'test_images')));

		return array(
			array('test_blog', $children, $children['test_blog']),
			array('test_post', $children, array('test_tags', 'test_author', 'test_images')),
			array('test_tags', $children, NULL),
			array('test_images', $children, NULL),
		);
	}

	/**
	 * @dataProvider data_get_current_children
	 */
	public function test_get_current_children($current, $children, $expected)
	{

		$this->assertEquals($expected, Jam_Behavior_Cascade::get_current_children($current, $children));
	}

	public function test_collect_children()
	{
		$model = Jam::find('test_blog', 1);

		$collected = Jam_Behavior_Cascade::collect_models($model, array('test_posts'));

		$this->assertEquals((string) $model, (string) $collected[0]);

		foreach ($model->test_posts as $i => $post)
		{
			$this->assertEquals((string) $post, (string) $collected[$i+1]);
		}

		$collected = Jam_Behavior_Cascade::collect_models($model, array('test_posts' => array('test_author', 'test_tags', 'test_images')));

		$expected = array(
			$model,
			$model->test_posts[0],
			$model->test_posts[0]->test_author,
			$model->test_posts[0]->test_tags[0],
			$model->test_posts[0]->test_tags[1],
			$model->test_posts[0]->test_tags[2],
			$model->test_posts[0]->test_tags[3],
			$model->test_posts[0]->test_tags[4],
			$model->test_posts[0]->test_images[0],
		);

		foreach ($expected as $i => $item)
		{
			$this->assertEquals((string) $item, (string) $collected[$i], 'Collection item '.$i.' must be "'.$item.'"');
		}
	}


} // End Jam_Builder_SelectTest
