<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.nested
 */
class Jam_Behavior_NestedTest extends Unittest_Jam_Database_TestCase {

	public function test_all_parents()
	{
		$category = Jam::find('test_category', 5);
		$this->assertEquals(array(1, 3), $category->parents()->ids());
	}

	public function test_parent()
	{
		$three = Jam::find('test_category', 3);
		$this->assertEquals(1, $three->parent->id());

		$this->assertFalse($three->is_root());
		$this->assertTrue($three->parent->is_root());
	}

	public function test_select()
	{
		$one = Jam::find('test_category', 1);

		$this->assertEquals(array(3), $one->children->ids());

		$top = Jam::find('test_category')->root()->ids();
		$this->assertEquals(array(1, 2, 4), $top);

		$top = Jam::find('test_category')->root(TRUE)->ids();
		$this->assertEquals(array(1, 2, 4), $top);

		$top = Jam::find('test_category')->root(FALSE)->ids();
		$this->assertEquals(array(3, 5), $top);
	}

	public function test_children_normal()
	{
		$cat = Jam::find('test_category', array(1, 2, 3, 4))->as_array();

		$cat[1]->children = array($cat[2], $cat[3]);
		$cat[0]->children = array($cat[1]);
		$cat[0]->save();

		$this->assertEquals($cat[0]->id, $cat[1]->parent->id);
		$this->assertEquals($cat[1]->id, $cat[2]->parent->id);
		$this->assertEquals($cat[1]->id, $cat[3]->parent->id);

		$this->assertCount(1, $cat[0]->children);
		$this->assertTrue($cat[0]->children->has($cat[1]));
		
		$this->assertCount(2, $cat[1]->children);
		$this->assertTrue($cat[1]->children->has($cat[2]));
		$this->assertTrue($cat[1]->children->has($cat[3]));
	}

	public function test_children_deep()
	{
		$post = Jam::build('test_post')->load_fields(array('id' => 1));

		$post->test_categories = array(
			array('name' => 'root', 'children' => array(
				array('name' => 'child1'),
				array('name' => 'child2', 'children' => array(1, 2, 3))
			))
		);

		$post->save();

		$this->assertCount(1, $post->test_categories);
		$this->assertCount(2, $post->test_categories[0]->children);
		$this->assertCount(3, $post->test_categories[0]->children[1]->children);

		$this->assertTrue($post->test_categories->has('root'));
		$this->assertTrue($post->test_categories[0]->children->has('child1'));
		$this->assertTrue($post->test_categories[0]->children->has('child2'));
		$this->assertTrue($post->test_categories[0]->children[1]->children->has('Category One'));
	}

} // End Jam_Builder_SelectTest