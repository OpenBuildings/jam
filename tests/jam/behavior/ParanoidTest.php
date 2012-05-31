<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.paranoid
 */
class Jam_Behavior_ParanoidTest extends Unittest_Jam_TestCase {

	
	public function test_select()
	{
		$normal = Jam::factory('test_video', 1);

		$this->assertTrue($normal->loaded());

		$deleted = Jam::factory('test_video', 3);
		$this->assertFalse($deleted->loaded());

		$this->assertCount(2, Jam::query('test_video')->select_all());
		$this->assertCount(3, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::ALL)->select_all());
		$this->assertCount(1, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::DELETED)->select_all());
	}

	public function test_set()
	{
		$video = Jam::factory('test_video', 1);

		$video->delete();

		$this->assertCount(1, Jam::query('test_video')->select_all());
		$this->assertCount(3, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::ALL)->select_all());
		$this->assertCount(2, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::DELETED)->select_all());

		$video = Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::ALL)->key(1)->select();

		$video->restore_delete();

		$this->assertCount(2, Jam::query('test_video')->select_all());
		$this->assertCount(3, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::ALL)->select_all());
		$this->assertCount(1, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::DELETED)->select_all());

		$video->real_delete();

		$this->assertCount(1, Jam::query('test_video')->select_all());
		$this->assertCount(2, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::ALL)->select_all());
		$this->assertCount(1, Jam::query('test_video')->deleted(Jam_Behavior_Paranoid::DELETED)->select_all());
	}
} // End Jam_Builder_SelectTest