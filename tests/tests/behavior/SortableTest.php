<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder Sortable Behavior functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.sortable
 */
class Jam_Behavior_SortableTest extends Testcase_Database {

	
	public function test_set()
	{
		$last_in_group = Jam::all('test_video')->where('group', '=', 'one')->first();

		$new = Jam::create('test_video', array('file' => 'file3.jpg', 'group' => 'one'));
		$first_in_group = Jam::create('test_video', array('file' => 'file3.jpg', 'group' => 'nogroup'));

		$this->assertGreaterThan($last_in_group->position, $new->position);
		$this->assertEquals(0, $first_in_group->position);
	}

	public function assertPositions($group, array $expected_positions)
	{
		$positions = Jam::all('test_video');
		if ($group)
		{
			$positions->where('group', '=', $group);
		}
		$this->assertSame($expected_positions, $positions->as_array('id', 'position'));
	}

	public function test_move_position_to()
	{
		$item5 = Jam::find('test_video', 5);
		$item2 = Jam::find('test_video', 2);
		$item5->move_position_to($item2);

		$this->assertPositions('one', array(
			'5' => '0',
			'2' => '1',
			'1' => '2',
		));

		$item5 = Jam::find('test_video', 5);
		$item1 = Jam::find('test_video', 1);
		$item5->move_position_to($item1);

		$this->assertPositions('one', array(
			'2' => '0',
			'1' => '1',
			'5' => '2',
		));

	}

	public function test_decrease_position()
	{
		$last = Jam::find('test_video', 5);

		$last->decrease_position();

		$this->assertPositions('one', array(
			'2' => '0',
			'5' => '1',
			'1' => '3',
		));

		$last->decrease_position();

		$this->assertPositions('one', array(
			'5' => '0',
			'2' => '1',
			'1' => '3',
		));

		$last->decrease_position();

		$this->assertPositions('one', array(
			'5' => '0',
			'2' => '1',
			'1' => '3',
		));
	}

	public function test_increase_position()
	{
		$first = Jam::find('test_video', 2);

		$first->increase_position();

		$this->assertPositions('one', array(
			'1' => '0',
			'2' => '1',
			'5' => '3',
		));

		$first->increase_position();

		$this->assertPositions('one', array(
			'1' => '0',
			'5' => '1',
			'2' => '3',
		));

		$first->increase_position();

		$this->assertPositions('one', array(
			'1' => '0',
			'5' => '1',
			'2' => '3',
		));
	}
	
	public function test_order()
	{
		$this->assertPositions('one', array(
			'2' => '0',
			'1' => '1',
			'5' => '3',
		));

		$this->assertPositions(NULL, array(
			'2' => '0',
			'1' => '1',
			'4' => '3',
			'5' => '3',
		));
	}

}