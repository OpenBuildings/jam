<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.sortable
 */
class Jam_Behavior_SortableTest extends Unittest_Jam_TestCase {

	
	public function test_set()
	{
		$last = Jam::factory('test_video', 1);
		$new = Jam::factory('test_video')->set('name', 'file3.jpg')->save();

		$this->assertGreaterThan($last->position, $new->position);
	}

	public function test_order()
	{
		$videos = Jam::query('test_video')->select_all();

		$this->assertEquals(0, $videos[0]->position);
		$this->assertEquals(1, $videos[1]->position);
	}

} // End Jam_Builder_SelectTest