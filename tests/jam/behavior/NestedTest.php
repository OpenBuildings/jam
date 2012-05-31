<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.nested
 */
class Jam_Behavior_NestedTest extends Unittest_Jam_TestCase {

	
	public function test_parent()
	{
		$three = Jam::factory('test_category', 3);
		$this->assertEquals(1, $three->parent->id());

		$parents = $three->parents();
		$this->assertEquals(1, $parents[0]->id());

		$this->assertFalse($three->is_root());
		$this->assertTrue($three->parent->is_root());
	}

	public function test_select()
	{
		$one = Jam::factory('test_category', 1);

		$this->assertEquals(array(3), $one->children->ids());

		$top = Jam::query('test_category')->root()->select_all()->ids();
		$this->assertEquals(array(1, 2, 4), $top);
	}

	public function test_children()
	{
		$cat = Jam::query('test_category')->find_insist(array(1, 2, 3, 4))->as_array();

		$cat[1]->children = array($cat[2], $cat[3]);
		$cat[0]->children = array($cat[1]);

		$cat[] = Jam::query('test_category')->find(array(1, 2, 3, 4));
		$cat[0]->save();

		$this->assertEquals($cat[0]->id, $cat[1]->parent->id);
		$this->assertEquals($cat[1]->id, $cat[2]->parent->id);
		$this->assertEquals($cat[1]->id, $cat[3]->parent->id);

		$this->assertCount(1, $cat[0]->children);
		$this->assertTrue($cat[0]->children->exists($cat[1]));
		
		$this->assertCount(2, $cat[1]->children);
		$this->assertTrue($cat[1]->children->exists($cat[2]));
		$this->assertTrue($cat[1]->children->exists($cat[3]));
	}

	public function test_children_deep()
	{
		$author = Jam::factory('test_author');

		$author->test_categories = array(
			array('name' => 'root', 'children' => array(
				array('name' => 'child1'),
				array('name' => 'child2', 'children' => array(1, 2, 3))
			))
		);

		$author->save();

		$this->assertCount(1, $author->test_categories);
		$this->assertCount(2, $author->test_categories[0]->children);
		$this->assertCount(3, $author->test_categories[0]->children[1]->children);

		$this->assertTrue($author->test_categories->exists('root'));
		$this->assertTrue($author->test_categories[0]->children->exists('child1'));
		$this->assertTrue($author->test_categories[0]->children->exists('child2'));
		$this->assertTrue($author->test_categories[0]->children[1]->children->exists('Category One'));
	}

} // End Jam_Builder_SelectTest