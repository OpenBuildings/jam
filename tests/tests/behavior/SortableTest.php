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

	/**
	 * Integration test for:
	 *  - Kohana_Jam_Behavior_Sortable::model_before_create
	 *  - Kohana_Jam_Behavior_Sortable::builder_call_where_in_scope
	 *  - Kohana_Jam_Behavior_Sortable::model_call_get_position
	 *
	 * @coversNothing
	 */
	public function test_set()
	{
		$last_in_group = Jam::all('test_video')->where('group', '=', 'one')->first();

		$new = Jam::create('test_video', array('file' => 'file3.jpg', 'group' => 'one'));
		$first_in_group = Jam::create('test_video', array('file' => 'file3.jpg', 'group' => 'nogroup'));

		$this->assertGreaterThan($last_in_group->position, $new->position);
		$this->assertEquals(1, $first_in_group->position);
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

	/**
	 * @covers Kohana_Jam_Behavior_Sortable::model_call_move_position_to
	 */
	public function test_move_position_to()
	{
		$item5 = Jam::find('test_video', 5);
		$item2 = Jam::find('test_video', 2);
		$item5->move_position_to($item2);

		$this->assertPositions('one', array(
			'1' => '1',
			'5' => '2',
			'2' => '3',
		));

		$item5 = Jam::find('test_video', 5);
		$item1 = Jam::find('test_video', 1);
		$item5->move_position_to($item1);

		$this->assertPositions('one', array(
			'5' => '1',
			'1' => '2',
			'2' => '3',
		));

	}

	/**
	 * @covers Kohana_Jam_Behavior_Sortable::model_call_decrease_position
	 */
	public function test_decrease_position()
	{
		$last = Jam::find('test_video', 5);

		$last->decrease_position();

		$this->assertPositions('one', array(
			'1' => '1',
			'5' => '2',
			'2' => '3',
		));

		$last->decrease_position();

		$this->assertPositions('one', array(
			'5' => '1',
			'1' => '2',
			'2' => '3',
		));

		$last->decrease_position();

		$this->assertPositions('one', array(
			'5' => '1',
			'1' => '2',
			'2' => '3',
		));
	}

	/**
	 * @covers Kohana_Jam_Behavior_Sortable::model_call_increase_position
	 */
	public function test_increase_position()
	{
		$first = Jam::find('test_video', 1);

		$first->increase_position();

		$this->assertPositions('one', array(
			'2' => '1',
			'1' => '2',
			'5' => '3',
		));

		$first->increase_position();

		$this->assertPositions('one', array(
			'2' => '1',
			'5' => '2',
			'1' => '3',
		));

		$first->increase_position();

		$this->assertPositions('one', array(
			'2' => '1',
			'5' => '2',
			'1' => '3',
		));
	}

	/**
	 * Integration test for
	 *  - Kohana_Jam_Behavior_Sortable::builder_call_order_by_position
	 *  - Kohana_Jam_Behavior_Sortable::builder_before_select
	 *
	 * @coversNothing
	 */
	public function test_order()
	{
		$this->assertPositions('one', array(
			'1' => '1',
			'2' => '2',
			'5' => '3',
		));

		$this->assertPositions(NULL, array(
			'1' => '1',
			'2' => '2',
			'4' => '3',
			'5' => '3',
		));
	}

	public function data_get_position()
	{
		return array(
			array(
				array(),
				1,
			),
			array(
				array(
					'group' => 'non-existent-group'
				),
				1,
			),
			array(
				array(
					'group' => 'one'
				),
				4,
			),
			array(
				array(
					'group' => 'two'
				),
				4,
			),
		);
	}

	/**
	 * @dataProvider data_get_position
	 * @covers Kohana_Jam_Behavior_Sortable::model_call_get_position
	 */
	public function test_get_position($model_data, $expected_position)
	{
		$model = Jam::build('test_video', $model_data);

		$this->assertSame($expected_position, $model->get_position());
	}

	public function data_get_position_loaded()
	{
		return array(
			array(
				array(
					'id' => 100,
				),
				1,
			),
			array(
				array(
					'id' => 100,
					'group' => 'non-existent-$group'
				),
				1,
			),
			array(
				array(
					'id' => 100,
					'group' => 'one'
				),
				4,
			),
			array(
				array(
					'id' => 100,
					'group' => 'two'
				),
				4,
			),
		);
	}

	/**
	 * @dataProvider data_get_position_loaded
	 * @covers Kohana_Jam_Behavior_Sortable::model_call_get_position
	 */
	public function test_get_position_loaded($model_data, $expected_position)
	{
		$model = Jam::build('test_video')->load_fields($model_data);

		$this->assertSame($expected_position, $model->get_position());
	}

	/**
	 * @covers Jam_Behavior_Sortable::model_before_update
	 * @covers Jam_Behavior_Sortable::_is_scope_changed
	 */
	public function test_model_before_update()
	{
		$model = Jam::find('test_video', 1);

		$model->group = 'two';
		$model->save();
		$this->assertSame(4, $model->position);

		$model->file = 'test.jpg';
		$model->group = 'two';
		$model->save();
		$this->assertSame(4, $model->position);

		$model->file = 'testing.jpg';
		$model->save();
		$this->assertSame(4, $model->position);
	}


	public function data_model_before_create()
	{
		return array(
			array(
				array(),
				1,
			),
			array(
				array(
					'position' => 55,
				),
				55,
			),
			array(
				array(
					'position' => 3,
				),
				3,
			),
			array(
				array(
					'group' => 'non-existent-group'
				),
				1,
			),
			array(
				array(
					'position' => 666,
					'group' => 'non-existent-$group'
				),
				666,
			),
			array(
				array(
					'group' => 'one'
				),
				4,
			),
			array(
				array(
					'position' => 125,
					'group' => 'one'
				),
				125,
			),
			array(
				array(
					'group' => 'two'
				),
				4,
			),
		);
	}



	/**
	 * @dataProvider data_model_before_create
	 * @covers Jam_Behavior_Sortable::model_before_create
	 */
	public function test_model_before_create($model_data, $expected_position)
	{
		$model = Jam::build('test_video', $model_data)
			->save();

		$this->assertSame($expected_position, $model->position);
	}
}
